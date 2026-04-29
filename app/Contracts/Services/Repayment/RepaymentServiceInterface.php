<?php

namespace App\Contracts\Services\Repayment;

use App\Models\Repayment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface RepaymentServiceInterface
{
    public function create(array $data, array $debtAllocations): Repayment;

    public function update(Repayment $repayment, array $data): Repayment;

    public function delete(Repayment $repayment): bool;

    public function findById(int $id): ?Repayment;

    public function findByReference(string $reference): ?Repayment;

    public function getAll(array $filters = []): Collection;

    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function getByFarmer(int $farmerId): Collection;

    public function getByOperator(int $operatorId): Collection;

    public function getByDateRange(string $startDate, string $endDate): Collection;

    public function generateReference(): string;

    public function validateRepaymentData(array $data, ?Repayment $repayment = null): void;

    public function validateDebtAllocations(array $allocations): void;

    public function getTotalRepaidByPeriod(string $startDate, string $endDate): float;

    public function getRepaymentStats(): array;

    public function applyToDebts(Repayment $repayment, array $debtAllocations): Repayment;
}
