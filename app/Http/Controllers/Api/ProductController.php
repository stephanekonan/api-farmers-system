<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Product\ProductResource;
use App\Contracts\Services\Product\ProductServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private ProductServiceInterface $productService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['category_id', 'is_active', 'min_price', 'max_price', 'search']);

        $products = $request->has('page')
            ? $this->productService->getPaginated($filters, $request->get('per_page', 15))
            : $this->productService->getAll($filters);

        $response = $request->has('page')
            ? $products
            : ProductResource::collection($products);

        return response()->json($response);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'price_fcfa' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'is_active' => 'boolean',
        ]);

        $product = $this->productService->create($validated);

        return response()->json(ProductResource::make($product), 201);
    }

    public function show(int $id): JsonResponse
    {
        $product = $this->productService->findById($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        return response()->json(ProductResource::make($product));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $product = $this->productService->findById($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $validated = $request->validate([
            'product_name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'sometimes|exists:categories,id',
            'price_fcfa' => 'sometimes|numeric|min:0',
            'unit' => 'sometimes|string|max:50',
            'is_active' => 'boolean',
        ]);

        $updatedProduct = $this->productService->update($product, $validated);

        return response()->json(ProductResource::make($updatedProduct));
    }

    public function destroy(int $id): JsonResponse
    {
        $product = $this->productService->findById($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $this->productService->delete($product);

        return response()->json(['message' => 'Product deleted successfully.']);
    }

    public function activate(int $id): JsonResponse
    {
        $product = $this->productService->findById($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $this->productService->activate($product);

        return response()->json(['message' => 'Product activated successfully.']);
    }

    public function deactivate(int $id): JsonResponse
    {
        $product = $this->productService->findById($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $this->productService->deactivate($product);

        return response()->json(['message' => 'Product deactivated successfully.']);
    }

    public function updatePrice(Request $request, int $id): JsonResponse
    {
        $product = $this->productService->findById($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $validated = $request->validate([
            'price_fcfa' => 'required|numeric|min:0',
        ]);

        $updatedProduct = $this->productService->updatePrice($product, $validated['price_fcfa']);

        return response()->json(ProductResource::make($updatedProduct));
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'required|string|min:2',
        ]);

        $products = $this->productService->search($validated['q']);

        return response()->json(ProductResource::collection($products));
    }

    public function getByCategory(int $categoryId): JsonResponse
    {
        $products = $this->productService->getByCategory($categoryId);

        return response()->json(ProductResource::collection($products));
    }
}
