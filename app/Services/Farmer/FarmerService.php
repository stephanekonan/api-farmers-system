<?php

namespace App\Services\Farmer;

use App\Models\Farmer;
use App\Contracts\Services\Farmer\FarmerServiceInterface;
use App\Exceptions\Farmer\FarmerValidationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class FarmerService implements FarmerServiceInterface
{
    public function create(array $data): Farmer
    {
        $this->validateFarmerData($data);

        if (isset($data['card_identifier'])) {
            $data['card_identifier'] = $this->generateUniqueCardIdentifier($data['card_identifier']);
        }

        return Farmer::create($data);
    }

    public function update(Farmer $farmer, array $data): Farmer
    {
        $this->validateFarmerData($data, $farmer);

        if (isset($data['card_identifier']) && $data['card_identifier'] !== $farmer->card_identifier) {
            $data['card_identifier'] = $this->generateUniqueCardIdentifier($data['card_identifier']);
        }

        $farmer->update($data);

        return $farmer->fresh();
    }

    public function delete(Farmer $farmer): bool
    {
        if ($farmer->transactions()->exists()) {
            throw FarmerValidationException::cannotDeleteFarmerWithTransactions();
        }

        if ($farmer->debts()->exists()) {
            throw FarmerValidationException::cannotDeleteFarmerWithDebts();
        }

        return $farmer->delete();
    }

    public function findById(int $id): ?Farmer
    {
        return Farmer::find($id);
    }

    public function findByCardIdentifier(string $cardIdentifier): ?Farmer
    {
        return Farmer::where('card_identifier', $cardIdentifier)->first();
    }

    public function findByPhone(string $phone): ?Farmer
    {
        return Farmer::where('phone', $phone)->first();
    }

    public function getAll(array $filters = []): Collection
    {
        $query = Farmer::query();

        $this->applyFilters($query, $filters);

        return $query->get();
    }

    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Farmer::query();

        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    public function getByRegion(string $region): Collection
    {
        return Farmer::where('region', $region)->get();
    }

    public function getByVillage(string $village): Collection
    {
        return Farmer::where('village', $village)->get();
    }

    public function activate(Farmer $farmer): void
    {
        $farmer->update(['is_active' => true]);
    }

    public function deactivate(Farmer $farmer): void
    {
        $farmer->update(['is_active' => false]);
    }

    public function search(string $query): Collection
    {
        return Farmer::where(function ($q) use ($query) {
            $q->where('firstname', 'like', "%{$query}%")
              ->orWhere('lastname', 'like', "%{$query}%")
              ->orWhere('card_identifier', 'like', "%{$query}%")
              ->orWhere('phone', 'like', "%{$query}%")
              ->orWhere('village', 'like', "%{$query}%")
              ->orWhere('region', 'like', "%{$query}%");
        })->get();
    }

    public function updateCreditLimit(Farmer $farmer, float $creditLimit): Farmer
    {
        if ($creditLimit < 0) {
            throw FarmerValidationException::invalidCreditLimit();
        }

        $farmer->update(['credit_limit_fcfa' => $creditLimit]);

        return $farmer->fresh();
    }

    public function updateOutstandingDebt(Farmer $farmer, float $amount): Farmer
    {
        if ($amount < 0) {
            throw FarmerValidationException::invalidDebtAmount();
        }

        $farmer->update(['total_outstanding_debt' => $amount]);

        return $farmer->fresh();
    }

    public function getFarmersWithDebt(): Collection
    {
        return Farmer::where('total_outstanding_debt', '>', 0)->get();
    }

    public function getFarmersExceedingCreditLimit(): Collection
    {
        return Farmer::whereRaw('total_outstanding_debt > credit_limit_fcfa')->get();
    }

    public function getTotalOutstandingDebt(): float
    {
        return Farmer::sum('total_outstanding_debt');
    }

    public function getTotalCreditLimit(): float
    {
        return Farmer::sum('credit_limit_fcfa');
    }

    private function validateFarmerData(array $data, ?Farmer $farmer = null): void
    {
        if (isset($data['credit_limit_fcfa']) && $data['credit_limit_fcfa'] < 0) {
            throw FarmerValidationException::invalidCreditLimit();
        }

        if (isset($data['total_outstanding_debt']) && $data['total_outstanding_debt'] < 0) {
            throw FarmerValidationException::invalidDebtAmount();
        }

        if (isset($data['card_identifier'])) {
            $existingFarmer = Farmer::where('card_identifier', $data['card_identifier'])
                ->when($farmer, function ($query) use ($farmer) {
                    return $query->where('id', '!=', $farmer->id);
                })
                ->first();

            if ($existingFarmer) {
                throw FarmerValidationException::duplicateCardIdentifier($data['card_identifier']);
            }
        }

        if (isset($data['phone'])) {
            $existingFarmer = Farmer::where('phone', $data['phone'])
                ->when($farmer, function ($query) use ($farmer) {
                    return $query->where('id', '!=', $farmer->id);
                })
                ->first();

            if ($existingFarmer) {
                throw FarmerValidationException::duplicatePhone($data['phone']);
            }
        }
    }

    private function generateUniqueCardIdentifier(string $cardIdentifier): string
    {
        $normalized = Str::upper(Str::replace([' ', '-'], '', $cardIdentifier));
        $originalIdentifier = $normalized;
        $counter = 1;

        while (Farmer::where('card_identifier', $normalized)->exists()) {
            $normalized = "{$originalIdentifier}-{$counter}";
            $counter++;
        }

        return $normalized;
    }

    private function applyFilters($query, array $filters): void
    {
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['region'])) {
            $query->where('region', $filters['region']);
        }

        if (isset($filters['village'])) {
            $query->where('village', $filters['village']);
        }

        if (isset($filters['has_debt'])) {
            if ($filters['has_debt']) {
                $query->where('total_outstanding_debt', '>', 0);
            } else {
                $query->where('total_outstanding_debt', '=', 0);
            }
        }

        if (isset($filters['exceeding_credit'])) {
            if ($filters['exceeding_credit']) {
                $query->whereRaw('total_outstanding_debt > credit_limit_fcfa');
            } else {
                $query->whereRaw('total_outstanding_debt <= credit_limit_fcfa');
            }
        }

        if (isset($filters['min_credit_limit'])) {
            $query->where('credit_limit_fcfa', '>=', $filters['min_credit_limit']);
        }

        if (isset($filters['max_credit_limit'])) {
            $query->where('credit_limit_fcfa', '<=', $filters['max_credit_limit']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('firstname', 'like', "%{$filters['search']}%")
                  ->orWhere('lastname', 'like', "%{$filters['search']}%")
                  ->orWhere('card_identifier', 'like', "%{$filters['search']}%")
                  ->orWhere('phone', 'like', "%{$filters['search']}%")
                  ->orWhere('village', 'like', "%{$filters['search']}%")
                  ->orWhere('region', 'like', "%{$filters['search']}%");
            });
        }

        $query->orderBy('lastname')->orderBy('firstname');
    }
}
