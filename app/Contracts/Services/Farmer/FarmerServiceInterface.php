<?php

namespace App\Contracts\Services\Farmer;

use App\Models\Farmer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface FarmerServiceInterface
{
    public function create(array $data): Farmer;

    public function update(Farmer $farmer, array $data): Farmer;

    public function delete(Farmer $farmer): bool;

    public function findById(int $id): ?Farmer;

    public function findByCardIdentifier(string $cardIdentifier): ?Farmer;

    public function findByPhone(string $phone): ?Farmer;

    public function getAll(array $filters = []): Collection;

    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function getByRegion(string $region): Collection;

    public function getByVillage(string $village): Collection;

    public function activate(Farmer $farmer): void;

    public function deactivate(Farmer $farmer): void;

    public function search(string $query): Collection;

    public function updateCreditLimit(Farmer $farmer, float $creditLimit): Farmer;

    public function updateOutstandingDebt(Farmer $farmer, float $amount): Farmer;

    public function getFarmersWithDebt(): Collection;

    public function getFarmersExceedingCreditLimit(): Collection;

    public function getTotalOutstandingDebt(): float;

    public function getTotalCreditLimit(): float;
}
