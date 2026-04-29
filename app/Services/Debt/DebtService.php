<?php

namespace App\Services\Debt;

use App\Contracts\Services\Debt\DebtServiceInterface;
use App\Models\Debt;
use App\Models\Repayment;
use App\Models\RepaymentDebt;
use App\Models\Farmer;
use App\Exceptions\Debt\DebtNotFoundException;
use App\Exceptions\Debt\DebtValidationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DebtService implements DebtServiceInterface
{
    public function create(array $data): Debt
    {
        $this->validateDebtData($data);

        return Debt::create($data);
    }

    public function update(Debt $debt, array $data): Debt
    {
        $this->validateDebtData($data, $debt);

        $debt->update($data);

        return $debt->fresh();
    }

    public function delete(Debt $debt): bool
    {
        if ($debt->repayments()->exists()) {
            throw DebtValidationException::cannotDeleteDebtWithRepayments();
        }

        return $debt->delete();
    }

    public function findById(int $id): ?Debt
    {
        return Debt::with(['farmer', 'transaction', 'repayments'])->find($id);
    }

    public function getByFarmer(int $farmerId): Collection
    {
        return Debt::with(['transaction', 'repayments'])
            ->where('farmer_id', $farmerId)
            ->orderBy('incurred_at', 'desc')
            ->get();
    }

    public function getByTransaction(int $transactionId): Collection
    {
        return Debt::with(['farmer', 'repayments'])
            ->where('transaction_id', $transactionId)
            ->get();
    }

    public function getAll(array $filters = []): Collection
    {
        $query = Debt::with(['farmer', 'transaction', 'repayments']);

        $this->applyFilters($query, $filters);

        return $query->orderBy('incurred_at', 'desc')->get();
    }

    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Debt::with(['farmer', 'transaction', 'repayments']);

        $this->applyFilters($query, $filters);

        return $query->orderBy('incurred_at', 'desc')->paginate($perPage);
    }

    public function getOutstandingDebts(): Collection
    {
        return Debt::with(['farmer', 'transaction'])
            ->where('remaining_amount_fcfa', '>', 0)
            ->where('status', 'pending')
            ->orderBy('incurred_at', 'desc')
            ->get();
    }

    public function getOverdueDebts(): Collection
    {
        return Debt::with(['farmer', 'transaction'])
            ->where('remaining_amount_fcfa', '>', 0)
            ->where('status', 'pending')
            ->where('incurred_at', '<', now()->subDays(30))
            ->orderBy('incurred_at', 'desc')
            ->get();
    }

    public function getPaidDebts(): Collection
    {
        return Debt::with(['farmer', 'transaction', 'repayments'])
            ->where('status', 'paid')
            ->orderBy('fully_paid_at', 'desc')
            ->get();
    }

    public function addPayment(Debt $debt, Repayment $repayment, float $amount): Debt
    {
        $this->validatePaymentAmount($debt, $amount);

        return DB::transaction(function () use ($debt, $repayment, $amount) {
            RepaymentDebt::create([
                'repayment_id' => $repayment->id,
                'debt_id' => $debt->id,
                'amount_applied_fcfa' => $amount,
            ]);

            $this->updateRemainingAmount($debt);

            if ($debt->remaining_amount_fcfa <= 0) {
                $this->markAsFullyPaid($debt);
            }

            return $debt->fresh();
        });
    }

    public function updateRemainingAmount(Debt $debt): Debt
    {
        $totalPaid = $debt->repayments()
            ->sum('amount_applied_fcfa');

        $remainingAmount = max(0, $debt->original_amount_fcfa - $totalPaid);

        $debt->update([
            'paid_amount_fcfa' => $totalPaid,
            'remaining_amount_fcfa' => $remainingAmount,
        ]);

        return $debt->fresh();
    }

    public function markAsFullyPaid(Debt $debt): Debt
    {
        $debt->update([
            'status' => 'paid',
            'remaining_amount_fcfa' => 0,
            'fully_paid_at' => now(),
        ]);

        return $debt->fresh();
    }

    public function validateDebtData(array $data, ?Debt $debt = null): void
    {
        if (isset($data['farmer_id'])) {
            $farmer = Farmer::find($data['farmer_id']);
            if (!$farmer) {
                throw DebtValidationException::farmerNotFound($data['farmer_id']);
            }
        }

        if (isset($data['original_amount_fcfa']) && $data['original_amount_fcfa'] < 0) {
            throw DebtValidationException::invalidOriginalAmount();
        }

        if (isset($data['paid_amount_fcfa']) && $data['paid_amount_fcfa'] < 0) {
            throw DebtValidationException::invalidPaidAmount();
        }

        if (isset($data['remaining_amount_fcfa']) && $data['remaining_amount_fcfa'] < 0) {
            throw DebtValidationException::invalidRemainingAmount();
        }

        if (isset($data['status']) && !in_array($data['status'], ['pending', 'paid', 'cancelled'])) {
            throw DebtValidationException::invalidStatus();
        }
    }

    public function validatePaymentAmount(Debt $debt, float $amount): void
    {
        if ($amount <= 0) {
            throw DebtValidationException::invalidPaymentAmount();
        }

        if ($amount > $debt->remaining_amount_fcfa) {
            throw DebtValidationException::paymentExceedsRemainingAmount();
        }
    }

    public function getTotalOutstanding(): float
    {
        return Debt::where('remaining_amount_fcfa', '>', 0)
            ->sum('remaining_amount_fcfa');
    }

    public function getTotalPaid(): float
    {
        return Debt::sum('paid_amount_fcfa');
    }

    public function getDebtStats(): array
    {
        $totalOriginal = Debt::sum('original_amount_fcfa');
        $totalPaid = Debt::sum('paid_amount_fcfa');
        $totalOutstanding = Debt::where('remaining_amount_fcfa', '>', 0)
            ->sum('remaining_amount_fcfa');
        $overdueCount = Debt::where('remaining_amount_fcfa', '>', 0)
            ->where('status', 'pending')
            ->where('incurred_at', '<', now()->subDays(30))
            ->count();

        return [
            'total_original_amount' => $totalOriginal,
            'total_paid_amount' => $totalPaid,
            'total_outstanding_amount' => $totalOutstanding,
            'overdue_debts_count' => $overdueCount,
            'collection_rate' => $totalOriginal > 0 ? round(($totalPaid / $totalOriginal) * 100, 2) : 0,
        ];
    }

    public function getFarmerDebtSummary(int $farmerId): array
    {
        $debts = Debt::where('farmer_id', $farmerId)->get();
        
        $totalOriginal = $debts->sum('original_amount_fcfa');
        $totalPaid = $debts->sum('paid_amount_fcfa');
        $totalOutstanding = $debts->sum('remaining_amount_fcfa');
        $pendingCount = $debts->where('status', 'pending')->count();
        $paidCount = $debts->where('status', 'paid')->count();

        return [
            'total_original_amount' => $totalOriginal,
            'total_paid_amount' => $totalPaid,
            'total_outstanding_amount' => $totalOutstanding,
            'pending_debts_count' => $pendingCount,
            'paid_debts_count' => $paidCount,
            'collection_rate' => $totalOriginal > 0 ? round(($totalPaid / $totalOriginal) * 100, 2) : 0,
        ];
    }

    private function applyFilters($query, array $filters): void
    {
        if (isset($filters['farmer_id'])) {
            $query->where('farmer_id', $filters['farmer_id']);
        }

        if (isset($filters['transaction_id'])) {
            $query->where('transaction_id', $filters['transaction_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['has_outstanding'])) {
            if ($filters['has_outstanding']) {
                $query->where('remaining_amount_fcfa', '>', 0);
            } else {
                $query->where('remaining_amount_fcfa', '=', 0);
            }
        }

        if (isset($filters['is_overdue'])) {
            $query->where('remaining_amount_fcfa', '>', 0)
                  ->where('status', 'pending')
                  ->where('incurred_at', '<', now()->subDays(30));
        }

        if (isset($filters['start_date'])) {
            $query->whereDate('incurred_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->whereDate('incurred_at', '<=', $filters['end_date']);
        }

        if (isset($filters['min_amount'])) {
            $query->where('original_amount_fcfa', '>=', $filters['min_amount']);
        }

        if (isset($filters['max_amount'])) {
            $query->where('original_amount_fcfa', '<=', $filters['max_amount']);
        }
    }
}
