<?php

namespace App\Services\Repayment;

use App\Contracts\Services\Repayment\RepaymentServiceInterface;
use App\Contracts\Services\Debt\DebtServiceInterface;
use App\Models\Repayment;
use App\Models\Debt;
use App\Models\RepaymentDebt;
use App\Models\Farmer;
use App\Models\User;
use App\Exceptions\Repayment\RepaymentNotFoundException;
use App\Exceptions\Repayment\RepaymentValidationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RepaymentService implements RepaymentServiceInterface
{
    public function __construct(private DebtServiceInterface $debtService)
    {
    }

    public function create(array $data, array $debtAllocations): Repayment
    {
        $this->validateRepaymentData($data);
        $this->validateDebtAllocations($debtAllocations);

        return DB::transaction(function () use ($data, $debtAllocations) {
            $data['reference'] = $this->generateReference();
            $data['repaid_at'] = $data['repaid_at'] ?? now();

            $repayment = Repayment::create($data);

            $this->applyToDebts($repayment, $debtAllocations);

            return $repayment->fresh(['debts']);
        });
    }

    public function update(Repayment $repayment, array $data): Repayment
    {
        $this->validateRepaymentData($data, $repayment);

        $repayment->update($data);

        return $repayment->fresh();
    }

    public function delete(Repayment $repayment): bool
    {
        if ($repayment->debts()->exists()) {
            throw RepaymentValidationException::cannotDeleteRepaymentWithDebts();
        }

        return $repayment->delete();
    }

    public function findById(int $id): ?Repayment
    {
        return Repayment::with(['farmer', 'operator', 'debts'])->find($id);
    }

    public function findByReference(string $reference): ?Repayment
    {
        return Repayment::with(['farmer', 'operator', 'debts'])
            ->where('reference', $reference)
            ->first();
    }

    public function getAll(array $filters = []): Collection
    {
        $query = Repayment::with(['farmer', 'operator', 'debts']);

        $this->applyFilters($query, $filters);

        return $query->orderBy('repaid_at', 'desc')->get();
    }

    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Repayment::with(['farmer', 'operator', 'debts']);

        $this->applyFilters($query, $filters);

        return $query->orderBy('repaid_at', 'desc')->paginate($perPage);
    }

    public function getByFarmer(int $farmerId): Collection
    {
        return Repayment::with(['operator', 'debts'])
            ->where('farmer_id', $farmerId)
            ->orderBy('repaid_at', 'desc')
            ->get();
    }

    public function getByOperator(int $operatorId): Collection
    {
        return Repayment::with(['farmer', 'debts'])
            ->where('operator_id', $operatorId)
            ->orderBy('repaid_at', 'desc')
            ->get();
    }

    public function getByDateRange(string $startDate, string $endDate): Collection
    {
        return Repayment::with(['farmer', 'operator', 'debts'])
            ->whereBetween('repaid_at', [$startDate, $endDate])
            ->orderBy('repaid_at', 'desc')
            ->get();
    }

    public function generateReference(): string
    {
        $prefix = 'PAY-' . date('Y');
        $counter = 1;
        
        do {
            $reference = $prefix . str_pad($counter, 6, '0', STR_PAD_LEFT);
            $counter++;
        } while (Repayment::where('reference', $reference)->exists());

        return $reference;
    }

    public function validateRepaymentData(array $data, ?Repayment $repayment = null): void
    {
        if (isset($data['farmer_id'])) {
            $farmer = Farmer::find($data['farmer_id']);
            if (!$farmer) {
                throw RepaymentValidationException::farmerNotFound($data['farmer_id']);
            }
            
            if (!$farmer->is_active) {
                throw RepaymentValidationException::farmerNotActive();
            }
        }

        if (isset($data['operator_id'])) {
            $operator = User::find($data['operator_id']);
            if (!$operator) {
                throw RepaymentValidationException::operatorNotFound($data['operator_id']);
            }
        }

        if (isset($data['commodity_kg']) && $data['commodity_kg'] <= 0) {
            throw RepaymentValidationException::invalidCommodityKg();
        }

        if (isset($data['commodity_rate_fcfa_per_kg']) && $data['commodity_rate_fcfa_per_kg'] < 0) {
            throw RepaymentValidationException::invalidCommodityRate();
        }

        if (isset($data['total_fcfa_value']) && $data['total_fcfa_value'] < 0) {
            throw RepaymentValidationException::invalidTotalValue();
        }
    }

    public function validateDebtAllocations(array $allocations): void
    {
        if (empty($allocations)) {
            throw RepaymentValidationException::noDebtAllocationsProvided();
        }

        $totalAllocation = 0;

        foreach ($allocations as $allocation) {
            if (!isset($allocation['debt_id']) || !isset($allocation['amount_applied_fcfa'])) {
                throw RepaymentValidationException::invalidAllocationFormat();
            }

            $debt = Debt::find($allocation['debt_id']);
            if (!$debt) {
                throw RepaymentValidationException::debtNotFound($allocation['debt_id']);
            }

            if ($allocation['amount_applied_fcfa'] <= 0) {
                throw RepaymentValidationException::invalidAllocationAmount();
            }

            if ($allocation['amount_applied_fcfa'] > $debt->remaining_amount_fcfa) {
                throw RepaymentValidationException::allocationExceedsRemainingAmount();
            }

            $totalAllocation += $allocation['amount_applied_fcfa'];
        }

        // Note: This validation would need the repayment total value
        // For now, we'll just ensure allocations are positive
        if ($totalAllocation <= 0) {
            throw RepaymentValidationException::invalidTotalAllocation();
        }
    }

    public function getTotalRepaidByPeriod(string $startDate, string $endDate): float
    {
        return Repayment::whereBetween('repaid_at', [$startDate, $endDate])
            ->sum('total_fcfa_value');
    }

    public function getRepaymentStats(): array
    {
        $today = now()->startOfDay();
        $thisMonth = now()->startOfMonth();

        return [
            'today_total' => Repayment::whereDate('repaid_at', $today)->sum('total_fcfa_value'),
            'month_total' => Repayment::whereDate('repaid_at', '>=', $thisMonth)->sum('total_fcfa_value'),
            'total_repayments' => Repayment::count(),
            'total_commodity_kg' => Repayment::sum('commodity_kg'),
        ];
    }

    public function applyToDebts(Repayment $repayment, array $debtAllocations): Repayment
    {
        foreach ($debtAllocations as $allocation) {
            $debt = Debt::findOrFail($allocation['debt_id']);
            
            RepaymentDebt::create([
                'repayment_id' => $repayment->id,
                'debt_id' => $debt->id,
                'amount_applied_fcfa' => $allocation['amount_applied_fcfa'],
            ]);

            $this->debtService->updateRemainingAmount($debt);

            if ($debt->remaining_amount_fcfa <= 0) {
                $this->debtService->markAsFullyPaid($debt);
            }
        }

        return $repayment->fresh();
    }

    private function applyFilters($query, array $filters): void
    {
        if (isset($filters['farmer_id'])) {
            $query->where('farmer_id', $filters['farmer_id']);
        }

        if (isset($filters['operator_id'])) {
            $query->where('operator_id', $filters['operator_id']);
        }

        if (isset($filters['start_date'])) {
            $query->whereDate('repaid_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->whereDate('repaid_at', '<=', $filters['end_date']);
        }

        if (isset($filters['min_amount'])) {
            $query->where('total_fcfa_value', '>=', $filters['min_amount']);
        }

        if (isset($filters['max_amount'])) {
            $query->where('total_fcfa_value', '<=', $filters['max_amount']);
        }

        if (isset($filters['reference'])) {
            $query->where('reference', 'like', "%{$filters['reference']}%");
        }

        if (isset($filters['min_kg'])) {
            $query->where('commodity_kg', '>=', $filters['min_kg']);
        }

        if (isset($filters['max_kg'])) {
            $query->where('commodity_kg', '<=', $filters['max_kg']);
        }
    }
}
