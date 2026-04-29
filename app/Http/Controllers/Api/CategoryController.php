<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Product\CategoryResource;
use App\Contracts\Services\Product\CategoryServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(private CategoryServiceInterface $categoryService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['parent_id', 'is_active', 'depth', 'search']);

        $categories = $request->has('page')
            ? $this->categoryService->getPaginated($filters, $request->get('per_page', 15))
            : $this->categoryService->getAll($filters);

        $response = $request->has('page')
            ? $categories
            : CategoryResource::collection($categories);

        return response()->json($response);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'boolean',
        ]);

        $category = $this->categoryService->create($validated);

        return response()->json(CategoryResource::make($category), 201);
    }

    public function show(int $id): JsonResponse
    {
        $category = $this->categoryService->findById($id);

        if (!$category) {
            return response()->json(['message' => 'Category not found.'], 404);
        }

        return response()->json(CategoryResource::make($category));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $category = $this->categoryService->findById($id);

        if (!$category) {
            return response()->json(['message' => 'Category not found.'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'boolean',
        ]);

        $updatedCategory = $this->categoryService->update($category, $validated);

        return response()->json(CategoryResource::make($updatedCategory));
    }

    public function destroy(int $id): JsonResponse
    {
        $category = $this->categoryService->findById($id);

        if (!$category) {
            return response()->json(['message' => 'Category not found.'], 404);
        }

        $this->categoryService->delete($category);

        return response()->json(['message' => 'Category deleted successfully.']);
    }

    public function tree(): JsonResponse
    {
        $tree = $this->categoryService->getTree();

        return response()->json(CategoryResource::collection($tree));
    }

    public function rootCategories(): JsonResponse
    {
        $categories = $this->categoryService->getRootCategories();

        return response()->json(CategoryResource::collection($categories));
    }

    public function children(int $parentId): JsonResponse
    {
        $children = $this->categoryService->getChildren($parentId);

        return response()->json(CategoryResource::collection($children));
    }

    public function activate(int $id): JsonResponse
    {
        $category = $this->categoryService->findById($id);

        if (!$category) {
            return response()->json(['message' => 'Category not found.'], 404);
        }

        $this->categoryService->activate($category);

        return response()->json(['message' => 'Category activated successfully.']);
    }

    public function deactivate(int $id): JsonResponse
    {
        $category = $this->categoryService->findById($id);

        if (!$category) {
            return response()->json(['message' => 'Category not found.'], 404);
        }

        $this->categoryService->deactivate($category);

        return response()->json(['message' => 'Category deactivated successfully.']);
    }

    public function move(Request $request, int $id): JsonResponse
    {
        $category = $this->categoryService->findById($id);

        if (!$category) {
            return response()->json(['message' => 'Category not found.'], 404);
        }

        $validated = $request->validate([
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        $movedCategory = $this->categoryService->move($category, $validated['parent_id']);

        return response()->json(CategoryResource::make($movedCategory));
    }
}
