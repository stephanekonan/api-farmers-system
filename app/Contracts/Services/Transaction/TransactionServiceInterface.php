<?php

namespace App\Contracts\Services\Transaction;

use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface TransactionServiceInterface
{
    public function create(array $data, array $items): Transaction;

    public function update(Transaction $transaction, array $data): Transaction;

    public function delete(Transaction $transaction): bool;

    public function findById(int $id): ?Transaction;

    public function findByReference(string $reference): ?Transaction;

    public function getAll(array $filters = []): Collection;

    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function getByFarmer(int $farmerId): Collection;

    public function getByOperator(int $operatorId): Collection;

    public function getByStatus(string $status): Collection;

    public function getByDateRange(string $startDate, string $endDate): Collection;

    public function addItem(Transaction $transaction, array $itemData): TransactionItem;

    public function updateItem(TransactionItem $item, array $data): TransactionItem;

    public function removeItem(TransactionItem $item): bool;

    public function calculateTotals(Transaction $transaction): Transaction;

    public function generateReference(): string;

    public function validateTransactionData(array $data, ?Transaction $transaction = null): void;

    public function validateItemData(array $itemData, ?TransactionItem $item = null): void;

    public function getTotalByPeriod(string $startDate, string $endDate): float;

    public function getTransactionStats(): array;
}
