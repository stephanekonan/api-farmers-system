<?php

namespace App\Services\Transaction;

use App\Contracts\Services\Transaction\TransactionServiceInterface;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Product;
use App\Models\Farmer;
use App\Models\User;
use App\Models\Debt;
use App\Exceptions\Transaction\TransactionValidationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TransactionService implements TransactionServiceInterface
{
    public function create(array $data, array $items): Transaction
    {
        $this->validateTransactionData($data);

        if (empty($items)) {
            throw TransactionValidationException::noItemsProvided();
        }

        foreach ($items as $item) {
            $this->validateItemData($item);
        }

        return DB::transaction(function () use ($data, $items) {
            $data['reference'] = $this->generateReference();
            $data['transacted_at'] = $data['transacted_at'] ?? now();

            $transaction = Transaction::create($data);

            foreach ($items as $itemData) {
                $this->addItem($transaction, $itemData);
            }

            $this->calculateTotals($transaction);

            if ($data['payment_method'] === 'CREDIT') {
                Debt::create([
                    'farmer_id' => $transaction->farmer_id,
                    'transaction_id' => $transaction->id,
                    'original_amount_fcfa' => $transaction->total_fcfa,
                    'paid_amount_fcfa' => 0,
                    'remaining_amount_fcfa' => $transaction->total_fcfa,
                    'status' => 'pending',
                    'incurred_at' => $transaction->transacted_at,
                ]);
            }

            return $transaction->fresh(['transactionItems.product', 'farmer', 'operator']);
        });
    }

    public function update(Transaction $transaction, array $data): Transaction
    {
        $this->validateTransactionData($data, $transaction);

        $transaction->update($data);

        if (isset($data['payment_method']) ||
            isset($data['interest_rate']) ||
            isset($data['subtotal_fcfa'])) {
            $this->calculateTotals($transaction);
        }

        return $transaction->fresh();
    }

    public function delete(Transaction $transaction): bool
    {
        if ($transaction->transactionItems()->exists()) {
            throw TransactionValidationException::cannotDeleteTransactionWithItems();
        }

        if ($transaction->debt()->exists()) {
            throw TransactionValidationException::cannotDeleteTransactionWithDebt();
        }

        return $transaction->delete();
    }

    public function findById(int $id): ?Transaction
    {
        return Transaction::with(['transactionItems.product', 'farmer', 'operator', 'debt'])->find($id);
    }

    public function findByReference(string $reference): ?Transaction
    {
        return Transaction::with(['transactionItems.product', 'farmer', 'operator', 'debt'])
            ->where('reference', $reference)
            ->first();
    }

    public function getAll(array $filters = []): Collection
    {
        $query = Transaction::with(['transactionItems.product', 'farmer', 'operator']);

        $this->applyFilters($query, $filters);

        return $query->orderBy('transacted_at', 'desc')->get();
    }

    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Transaction::with(['transactionItems.product', 'farmer', 'operator']);

        $this->applyFilters($query, $filters);

        return $query->orderBy('transacted_at', 'desc')->paginate($perPage);
    }

    public function getByFarmer(int $farmerId): Collection
    {
        return Transaction::with(['transactionItems.product', 'operator'])
            ->where('farmer_id', $farmerId)
            ->orderBy('transacted_at', 'desc')
            ->get();
    }

    public function getByOperator(int $operatorId): Collection
    {
        return Transaction::with(['transactionItems.product', 'farmer'])
            ->where('operator_id', $operatorId)
            ->orderBy('transacted_at', 'desc')
            ->get();
    }

    public function getByStatus(string $status): Collection
    {
        return Transaction::with(['transactionItems.product', 'farmer', 'operator'])
            ->where('status', $status)
            ->orderBy('transacted_at', 'desc')
            ->get();
    }

    public function getByDateRange(string $startDate, string $endDate): Collection
    {
        return Transaction::with(['transactionItems.product', 'farmer', 'operator'])
            ->whereBetween('transacted_at', [$startDate, $endDate])
            ->orderBy('transacted_at', 'desc')
            ->get();
    }

    public function addItem(Transaction $transaction, array $itemData): TransactionItem
    {
        $this->validateItemData($itemData);

        $product = Product::findOrFail($itemData['product_id']);

        $itemData['product_name'] = $product->product_name;
        $itemData['unit_price_fcfa'] = $itemData['unit_price_fcfa'] ?? $product->price_fcfa;
        $itemData['line_total_fcfa'] = $itemData['unit_price_fcfa'] * $itemData['quantity'];

        return $transaction->transactionItems()->create($itemData);
    }

    public function updateItem(TransactionItem $item, array $data): TransactionItem
    {
        $this->validateItemData($data, $item);

        if (isset($data['quantity']) || isset($data['unit_price_fcfa'])) {
            $data['line_total_fcfa'] = ($data['unit_price_fcfa'] ?? $item->unit_price_fcfa) *
                                       ($data['quantity'] ?? $item->quantity);
        }

        $item->update($data);

        $this->calculateTotals($item->transaction);

        return $item->fresh();
    }

    public function removeItem(TransactionItem $item): bool
    {
        $transaction = $item->transaction;

        $result = $item->delete();

        if ($result) {
            $this->calculateTotals($transaction);
        }

        return $result;
    }

    public function calculateTotals(Transaction $transaction): Transaction
    {
        $subtotal = $transaction->transactionItems()->sum('line_total_fcfa');

        $interestRate = $transaction->interest_rate ?? 0;
        $interestAmount = $subtotal * ($interestRate / 100);
        $total = $subtotal + $interestAmount;

        $transaction->update([
            'subtotal_fcfa' => $subtotal,
            'interest_amount_fcfa' => $interestAmount,
            'total_fcfa' => $total,
        ]);

        return $transaction->fresh();
    }

    public function generateReference(): string
    {
        $prefix = 'TRX-' . date('Y');
        $counter = 1;

        do {
            $reference = $prefix . str_pad($counter, 6, '0', STR_PAD_LEFT);
            $counter++;
        } while (Transaction::where('reference', $reference)->exists());

        return $reference;
    }

    public function validateTransactionData(array $data, ?Transaction $transaction = null): void
    {
        if (isset($data['farmer_id'])) {
            $farmer = Farmer::find($data['farmer_id']);
            if (!$farmer) {
                throw TransactionValidationException::farmerNotFound($data['farmer_id']);
            }

            if (!$farmer->is_active) {
                throw TransactionValidationException::farmerNotActive();
            }
        }

        if (isset($data['operator_id'])) {
            $operator = User::find($data['operator_id']);
            if (!$operator) {
                throw TransactionValidationException::operatorNotFound($data['operator_id']);
            }
        }

        if (isset($data['payment_method']) && !in_array($data['payment_method'], ['cash', 'credit'])) {
            throw TransactionValidationException::invalidPaymentMethod();
        }

        if (isset($data['interest_rate']) && $data['interest_rate'] < 0) {
            throw TransactionValidationException::invalidInterestRate();
        }
    }

    public function validateItemData(array $itemData, ?TransactionItem $item = null): void
    {
        if (isset($itemData['product_id'])) {
            $product = Product::find($itemData['product_id']);
            if (!$product) {
                throw TransactionValidationException::productNotFound($itemData['product_id']);
            }

            if (!$product->is_active) {
                throw TransactionValidationException::productNotActive();
            }
        }

        if (isset($itemData['quantity']) && $itemData['quantity'] <= 0) {
            throw TransactionValidationException::invalidQuantity();
        }

        if (isset($itemData['unit_price_fcfa']) && $itemData['unit_price_fcfa'] < 0) {
            throw TransactionValidationException::invalidUnitPrice();
        }
    }

    public function getTotalByPeriod(string $startDate, string $endDate): float
    {
        return Transaction::whereBetween('transacted_at', [$startDate, $endDate])
            ->sum('total_fcfa');
    }

    public function getTransactionStats(): array
    {
        $today = now()->startOfDay();
        $thisMonth = now()->startOfMonth();

        return [
            'today_total' => Transaction::whereDate('transacted_at', $today)->sum('total_fcfa'),
            'month_total' => Transaction::whereDate('transacted_at', '>=', $thisMonth)->sum('total_fcfa'),
            'total_transactions' => Transaction::count(),
            'credit_transactions' => Transaction::where('payment_method', 'credit')->count(),
            'cash_transactions' => Transaction::where('payment_method', 'cash')->count(),
        ];
    }

    private function applyFilters($query, array $filters): void
    {
        if (isset($filters['farmer_id'])) {
            $query->where('farmer_id', $filters['farmer_id']);
        }

        if (isset($filters['operator_id'])) {
            $query->where('operator_id', $filters['operator_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (isset($filters['start_date'])) {
            $query->whereDate('transacted_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->whereDate('transacted_at', '<=', $filters['end_date']);
        }

        if (isset($filters['min_amount'])) {
            $query->where('total_fcfa', '>=', $filters['min_amount']);
        }

        if (isset($filters['max_amount'])) {
            $query->where('total_fcfa', '<=', $filters['max_amount']);
        }

        if (isset($filters['reference'])) {
            $query->where('reference', 'like', "%{$filters['reference']}%");
        }
    }
}
