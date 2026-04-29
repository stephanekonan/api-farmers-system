<?php

namespace App\Services\Product;

use App\Contracts\Services\ProductServiceInterface;
use App\Models\Category;
use App\Models\Product;
use App\Exceptions\Product\ProductNotFoundException;
use App\Exceptions\Product\ProductValidationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class ProductService implements ProductServiceInterface
{
    public function create(array $data): Product
    {
        $this->validateProductData($data);

        $data['slug'] = $this->generateUniqueSlug($data['product_name']);

        return Product::create($data);
    }

    public function update(Product $product, array $data): Product
    {
        $this->validateProductData($data, $product);

        if (isset($data['product_name']) && $data['product_name'] !== $product->product_name) {
            $data['slug'] = $this->generateUniqueSlug($data['product_name']);
        }

        $product->update($data);

        return $product->fresh();
    }

    public function delete(Product $product): bool
    {
        if ($product->transactionItems()->exists()) {
            throw ProductValidationException::cannotDeleteProductWithTransactions();
        }

        return $product->delete();
    }

    public function findById(int $id): ?Product
    {
        return Product::find($id);
    }

    public function findBySlug(string $slug): ?Product
    {
        return Product::where('slug', $slug)->first();
    }

    public function getAll(array $filters = []): Collection
    {
        $query = Product::query();

        $this->applyFilters($query, $filters);

        return $query->get();
    }

    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Product::query();

        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    public function getByCategory(int $categoryId): Collection
    {
        $category = Category::find($categoryId);
        if (!$category) {
            throw ProductNotFoundException::categoryNotFound($categoryId);
        }

        return $category->products()->get();
    }

    public function activate(Product $product): void
    {
        $product->update(['is_active' => true]);
    }

    public function deactivate(Product $product): void
    {
        $product->update(['is_active' => false]);
    }

    public function search(string $query): Collection
    {
        return Product::where('product_name', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->get();
    }

    public function updatePrice(Product $product, float $price): Product
    {
        if ($price < 0) {
            throw ProductValidationException::invalidPrice();
        }

        $product->update(['price_fcfa' => $price]);

        return $product->fresh();
    }

    private function validateProductData(array $data, ?Product $product = null): void
    {
        if (isset($data['price_fcfa']) && $data['price_fcfa'] < 0) {
            throw ProductValidationException::invalidPrice();
        }

        if (isset($data['category_id'])) {
            $category = Category::find($data['category_id']);
            if (!$category) {
                throw ProductNotFoundException::categoryNotFound($data['category_id']);
            }
        }

        if (isset($data['product_name'])) {
            $existingProduct = Product::where('product_name', $data['product_name'])
                ->when($product, function ($query) use ($product) {
                    return $query->where('id', '!=', $product->id);
                })
                ->first();

            if ($existingProduct) {
                throw ProductValidationException::duplicateProductName($data['product_name']);
            }
        }
    }

    private function generateUniqueSlug(string $productName): string
    {
        $slug = Str::slug($productName);
        $originalSlug = $slug;
        $counter = 1;

        while (Product::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function applyFilters($query, array $filters): void
    {
        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['min_price'])) {
            $query->where('price_fcfa', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('price_fcfa', '<=', $filters['max_price']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('product_name', 'like', "%{$filters['search']}%")
                  ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        $query->with('category');
    }
}
