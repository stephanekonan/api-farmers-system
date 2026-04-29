<?php

namespace App\Contracts\Services\Product;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface CategoryServiceInterface
{
    public function create(array $data): Category;

    public function update(Category $category, array $data): Category;

    public function delete(Category $category): bool;

    public function findById(int $id): ?Category;

    public function findBySlug(string $slug): ?Category;

    public function getAll(array $filters = []): Collection;

    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function getRootCategories(): Collection;

    public function getChildren(int $parentId): Collection;

    public function getTree(): Collection;

    public function activate(Category $category): void;

    public function deactivate(Category $category): void;

    public function move(Category $category, ?int $parentId): Category;

    public function updateDepth(Category $category): void;
}
