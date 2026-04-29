<?php

namespace App\Contracts\Services\Product;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProductServiceInterface
{
    public function create(array $data): Product;

    public function update(Product $product, array $data): Product;

    public function delete(Product $product): bool;

    public function findById(int $id): ?Product;

    public function findBySlug(string $slug): ?Product;

    public function getAll(array $filters = []): Collection;

    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function getByCategory(int $categoryId): Collection;

    public function activate(Product $product): void;

    public function deactivate(Product $product): void;

    public function search(string $query): Collection;

    public function updatePrice(Product $product, float $price): Product;
}
