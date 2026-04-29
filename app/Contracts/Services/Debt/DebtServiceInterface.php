<?php

namespace App\Contracts\Services\Debt;

use App\Models\Debt;
use App\Models\Repayment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface DebtServiceInterface
{
    public function create(array $data): Debt;

    public function update(Debt $debt, array $data): Debt;

    public function delete(Debt $debt): bool;

    public function findById(int $id): ?Debt;

    public function getByFarmer(int $farmerId): Collection;

    public function getByTransaction(int $transactionId): Collection;

    public function getAll(array $filters = []): Collection;

    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function getOutstandingDebts(): Collection;

    public function getOverdueDebts(): Collection;

    public function getPaidDebts(): Collection;

    public function addPayment(Debt $debt, Repayment $repayment, float $amount): Debt;

    public function updateRemainingAmount(Debt $debt): Debt;

    public function markAsFullyPaid(Debt $debt): Debt;

    public function validateDebtData(array $data, ?Debt $debt = null): void;

    public function validatePaymentAmount(Debt $debt, float $amount): void;

    public function getTotalOutstanding(): float;

    public function getTotalPaid(): float;

    public function getDebtStats(): array;

    public function getFarmerDebtSummary(int $farmerId): array;
}
