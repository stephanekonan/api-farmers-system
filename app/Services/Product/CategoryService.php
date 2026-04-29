<?php

namespace App\Services\Product;

use App\Contracts\Services\CategoryServiceInterface;
use App\Models\Category;
use App\Exceptions\Category\CategoryNotFoundException;
use App\Exceptions\Category\CategoryValidationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class CategoryService implements CategoryServiceInterface
{
    public function create(array $data): Category
    {
        $this->validateCategoryData($data);

        $data['slug'] = $this->generateUniqueSlug($data['name']);

        if (isset($data['parent_id'])) {
            $data['depth'] = $this->calculateDepth($data['parent_id']);
        } else {
            $data['depth'] = 0;
        }

        return Category::create($data);
    }

    public function update(Category $category, array $data): Category
    {
        $this->validateCategoryData($data, $category);

        if (isset($data['name']) && $data['name'] !== $category->name) {
            $data['slug'] = $this->generateUniqueSlug($data['name']);
        }

        if (isset($data['parent_id']) && $data['parent_id'] !== $category->parent_id) {
            $data['depth'] = $this->calculateDepth($data['parent_id']);
            $this->updateChildrenDepth($category, $data['depth']);
        }

        $category->update($data);

        return $category->fresh();
    }

    public function delete(Category $category): bool
    {
        if ($category->children()->exists()) {
            throw CategoryValidationException::cannotDeleteCategoryWithChildren();
        }

        if ($category->products()->exists()) {
            throw CategoryValidationException::cannotDeleteCategoryWithProducts();
        }

        return $category->delete();
    }

    public function findById(int $id): ?Category
    {
        return Category::find($id);
    }

    public function findBySlug(string $slug): ?Category
    {
        return Category::where('slug', $slug)->first();
    }

    public function getAll(array $filters = []): Collection
    {
        $query = Category::query();

        $this->applyFilters($query, $filters);

        return $query->orderBy('depth')
            ->orderBy('category_name')
            ->get();
    }

    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Category::query();

        $this->applyFilters($query, $filters);

        return $query->orderBy('depth')
            ->orderBy('category_name')
            ->paginate($perPage);
    }

    public function getRootCategories(): Collection
    {
        return Category::whereNull('parent_id')
            ->orderBy('category_name')
            ->get();
    }

    public function getChildren(int $parentId): Collection
    {
        $parent = Category::find($parentId);
        if (!$parent) {
            throw CategoryNotFoundException::categoryNotFound($parentId);
        }

        return $parent->children()
            ->orderBy('category_name')
            ->get();
    }

    public function getTree(): Collection
    {
        return $this->buildTree($this->getRootCategories());
    }

    public function activate(Category $category): void
    {
        $category->update(['is_active' => true]);
    }

    public function deactivate(Category $category): void
    {
        $category->update(['is_active' => false]);
    }

    public function move(Category $category, ?int $parentId): Category
    {
        if ($parentId && $parentId === $category->id) {
            throw CategoryValidationException::cannotMoveToSelf();
        }

        if ($parentId && $this->isDescendant($category, $parentId)) {
            throw CategoryValidationException::cannotMoveToDescendant();
        }

        $depth = $parentId ? $this->calculateDepth($parentId) : 0;

        $category->update([
            'parent_id' => $parentId,
            'depth' => $depth,
        ]);

        $this->updateChildrenDepth($category, $depth);

        return $category->fresh();
    }

    public function updateDepth(Category $category): void
    {
        $depth = $category->parent_id ? $this->calculateDepth($category->parent_id) : 0;

        $category->update(['depth' => $depth]);
        $this->updateChildrenDepth($category, $depth);
    }

    private function validateCategoryData(array $data, ?Category $category = null): void
    {
        if (isset($data['parent_id'])) {
            if ($data['parent_id'] && $category && $data['parent_id'] === $category->id) {
                throw CategoryValidationException::cannotBeOwnParent();
            }

            if ($data['parent_id'] && $category && $this->isDescendant($category, $data['parent_id'])) {
                throw CategoryValidationException::cannotCreateCircularReference();
            }

            if ($data['parent_id'] && !Category::find($data['parent_id'])) {
                throw CategoryNotFoundException::parentNotFound($data['parent_id']);
            }
        }

        if (isset($data['category_name'])) {
            $existingCategory = Category::where('category_name', $data['category_name'])
                ->when($category, function ($query) use ($category) {
                    return $query->where('id', '!=', $category->id);
                })
                ->first();

            if ($existingCategory) {
                throw CategoryValidationException::duplicateCategoryName($data['name']);
            }
        }
    }

    private function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (Category::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function calculateDepth(int $parentId): int
    {
        $parent = Category::find($parentId);
        if (!$parent) {
            return 0;
        }

        return $parent->depth + 1;
    }

    private function updateChildrenDepth(Category $category, int $newDepth): void
    {
        $category->children()->get()->each(function ($child) use ($newDepth) {
            $child->update(['depth' => $newDepth + 1]);
            $this->updateChildrenDepth($child, $newDepth + 1);
        });
    }

    private function isDescendant(Category $category, int $potentialDescendantId): bool
    {
        $descendant = Category::find($potentialDescendantId);
        if (!$descendant) {
            return false;
        }

        $current = $descendant->parent;
        while ($current) {
            if ($current->id === $category->id) {
                return true;
            }
            $current = $current->parent;
        }

        return false;
    }

    private function buildTree(Collection $categories): Collection
    {
        return $categories->map(function ($category) {
            $category->children = $this->buildTree($category->children);
            return $category;
        });
    }

    private function applyFilters($query, array $filters): void
    {
        if (isset($filters['parent_id'])) {
            $query->where('parent_id', $filters['parent_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['depth'])) {
            $query->where('depth', $filters['depth']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }
    }
}
