<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    public function test_index_categories_as_authenticated_user(): void
    {
        $this->authenticateOperator();

        Category::factory()->count(3)->create();

        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'category_name',
                    'slug',
                    'description',
                    'parent_id',
                    'depth',
                    'is_active',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonCount(3);
    }

    public function test_index_categories_without_authentication(): void
    {
        $response = $this->getJson('/api/categories');

        $response->assertStatus(401);
    }

    public function test_store_category_as_authenticated_user(): void
    {
        $this->authenticateOperator();

        $categoryData = [
            'category_name' => 'Test Category',
            'slug' => 'test-category',
            'description' => 'This is a test category',
            'parent_id' => null,
            'depth' => 0,
            'is_active' => true,
        ];

        $response = $this->postJson('/api/categories', $categoryData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'category_name',
                'slug',
                'description',
                'parent_id',
                'depth',
                'is_active',
                'created_at',
                'updated_at',
            ]);

        $this->assertDatabaseHas('categories', [
            'category_name' => 'Test Category',
            'slug' => 'test-category',
            'depth' => 0,
        ]);
    }

    public function test_store_category_without_authentication(): void
    {
        $categoryData = [
            'category_name' => 'Test Category',
            'slug' => 'test-category',
            'depth' => 0,
        ];

        $response = $this->postJson('/api/categories', $categoryData);

        $response->assertStatus(401);
    }

    public function test_store_category_with_validation_errors(): void
    {
        $this->authenticateOperator();

        $response = $this->postJson('/api/categories', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'category_name',
                'slug',
                'depth',
            ]);
    }

    public function test_store_category_with_duplicate_slug(): void
    {
        $this->authenticateOperator();

        $existingCategory = Category::factory()->create(['slug' => 'duplicate-slug']);

        $categoryData = [
            'category_name' => 'Another Category',
            'slug' => 'duplicate-slug',
            'depth' => 0,
        ];

        $response = $this->postJson('/api/categories', $categoryData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_show_category_as_authenticated_user(): void
    {
        $this->authenticateOperator();

        $category = Category::factory()->create();

        $response = $this->getJson("/api/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'category_name',
                'slug',
                'description',
                'parent_id',
                'depth',
                'is_active',
                'created_at',
                'updated_at',
            ])
            ->assertJson([
                'id' => $category->id,
                'category_name' => $category->category_name,
                'slug' => $category->slug,
            ]);
    }

    public function test_show_category_without_authentication(): void
    {
        $category = Category::factory()->create();

        $response = $this->getJson("/api/categories/{$category->id}");

        $response->assertStatus(401);
    }

    public function test_show_nonexistent_category(): void
    {
        $this->authenticateOperator();

        $response = $this->getJson('/api/categories/999999');

        $response->assertStatus(404);
    }

    public function test_update_category_as_authenticated_user(): void
    {
        $this->authenticateOperator();

        $category = Category::factory()->create();

        $updateData = [
            'category_name' => 'Updated Category',
            'description' => 'Updated description',
            'is_active' => false,
        ];

        $response = $this->putJson("/api/categories/{$category->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson($updateData);

        $this->assertDatabaseHas('categories', array_merge(['id' => $category->id], $updateData));
    }

    public function test_update_category_without_authentication(): void
    {
        $category = Category::factory()->create();

        $response = $this->putJson("/api/categories/{$category->id}", [
            'category_name' => 'Updated Category',
        ]);

        $response->assertStatus(401);
    }

    public function test_delete_category_as_authenticated_user(): void
    {
        $this->authenticateOperator();

        $category = Category::factory()->create();

        $response = $this->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_delete_category_without_authentication(): void
    {
        $category = Category::factory()->create();

        $response = $this->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(401);
    }

    public function test_get_category_products(): void
    {
        $this->authenticateOperator();

        $category = Category::factory()->create();
        $product1 = Product::factory()->create(['category_id' => $category->id]);
        $product2 = Product::factory()->create(['category_id' => $category->id]);
        Product::factory()->create(); // Product for different category

        $response = $this->getJson("/api/categories/{$category->id}/products");

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'product_name',
                    'slug',
                    'category_id',
                    'price_fcfa',
                    'unit',
                    'is_active',
                ],
            ]);

        foreach ($response->json() as $product) {
            $this->assertEquals($category->id, $product['category_id']);
        }
    }

    public function test_get_category_products_without_authentication(): void
    {
        $category = Category::factory()->create();

        $response = $this->getJson("/api/categories/{$category->id}/products");

        $response->assertStatus(401);
    }

    public function test_get_root_categories(): void
    {
        $this->authenticateOperator();

        $rootCategory1 = Category::factory()->create(['parent_id' => null, 'depth' => 0]);
        $rootCategory2 = Category::factory()->create(['parent_id' => null, 'depth' => 0]);
        $childCategory = Category::factory()->create(['parent_id' => $rootCategory1->id, 'depth' => 1]);

        $response = $this->getJson('/api/categories/root/root');

        $response->assertStatus(200)
            ->assertJsonCount(2);

        foreach ($response->json() as $category) {
            $this->assertNull($category['parent_id']);
            $this->assertEquals(0, $category['depth']);
        }
    }

    public function test_get_root_categories_without_authentication(): void
    {
        $response = $this->getJson('/api/categories/root/root');

        $response->assertStatus(401);
    }

    public function test_get_category_children(): void
    {
        $this->authenticateOperator();

        $parentCategory = Category::factory()->create(['parent_id' => null, 'depth' => 0]);
        $child1 = Category::factory()->create(['parent_id' => $parentCategory->id, 'depth' => 1]);
        $child2 = Category::factory()->create(['parent_id' => $parentCategory->id, 'depth' => 1]);
        Category::factory()->create(['parent_id' => null, 'depth' => 0]); // Different parent

        $response = $this->getJson("/api/categories/{$parentCategory->id}/children");

        $response->assertStatus(200)
            ->assertJsonCount(2);

        foreach ($response->json() as $category) {
            $this->assertEquals($parentCategory->id, $category['parent_id']);
            $this->assertEquals(1, $category['depth']);
        }
    }

    public function test_get_category_children_without_authentication(): void
    {
        $parentCategory = Category::factory()->create();

        $response = $this->getJson("/api/categories/{$parentCategory->id}/children");

        $response->assertStatus(401);
    }

    public function test_hierarchical_category_structure(): void
    {
        $this->authenticateOperator();

        $rootCategory = Category::factory()->create(['parent_id' => null, 'depth' => 0]);
        $childCategory = Category::factory()->create(['parent_id' => $rootCategory->id, 'depth' => 1]);
        $grandchildCategory = Category::factory()->create(['parent_id' => $childCategory->id, 'depth' => 2]);

        // Test root categories
        $response = $this->getJson('/api/categories/root/root');
        $response->assertJsonCount(1);
        $this->assertEquals($rootCategory->id, $response->json()[0]['id']);

        // Test children of root
        $response = $this->getJson("/api/categories/{$rootCategory->id}/children");
        $response->assertJsonCount(1);
        $this->assertEquals($childCategory->id, $response->json()[0]['id']);

        // Test children of child
        $response = $this->getJson("/api/categories/{$childCategory->id}/children");
        $response->assertJsonCount(1);
        $this->assertEquals($grandchildCategory->id, $response->json()[0]['id']);

        // Test children of grandchild (should be empty)
        $response = $this->getJson("/api/categories/{$grandchildCategory->id}/children");
        $response->assertJsonCount(0);
    }

    public function test_category_with_null_parent(): void
    {
        $this->authenticateOperator();

        $categoryData = [
            'category_name' => 'Root Category',
            'slug' => 'root-category',
            'parent_id' => null,
            'depth' => 0,
            'is_active' => true,
        ];

        $response = $this->postJson('/api/categories', $categoryData);

        $response->assertStatus(201)
            ->assertJson([
                'parent_id' => null,
                'depth' => 0,
            ]);

        $this->assertDatabaseHas('categories', [
            'parent_id' => null,
            'depth' => 0,
        ]);
    }

    public function test_category_with_parent(): void
    {
        $this->authenticateOperator();

        $parentCategory = Category::factory()->create(['depth' => 0]);

        $categoryData = [
            'category_name' => 'Child Category',
            'slug' => 'child-category',
            'parent_id' => $parentCategory->id,
            'depth' => 1,
            'is_active' => true,
        ];

        $response = $this->postJson('/api/categories', $categoryData);

        $response->assertStatus(201)
            ->assertJson([
                'parent_id' => $parentCategory->id,
                'depth' => 1,
            ]);

        $this->assertDatabaseHas('categories', [
            'parent_id' => $parentCategory->id,
            'depth' => 1,
        ]);
    }

    public function test_category_activation_deactivation(): void
    {
        $this->authenticateOperator();

        $category = Category::factory()->create(['is_active' => true]);

        // Deactivate
        $response = $this->putJson("/api/categories/{$category->id}", [
            'is_active' => false,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'is_active' => false,
            ]);

        // Reactivate
        $response = $this->putJson("/api/categories/{$category->id}", [
            'is_active' => true,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'is_active' => true,
            ]);
    }

    public function test_category_depth_validation(): void
    {
        $this->authenticateOperator();

        $invalidData = [
            'category_name' => 'Invalid Category',
            'slug' => 'invalid-category',
            'depth' => -1, // Negative depth
        ];

        $response = $this->postJson('/api/categories', $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['depth']);
    }

    public function test_category_with_long_description(): void
    {
        $this->authenticateOperator();

        $longDescription = 'This is a very long description that contains multiple sentences and detailed information about the category. It should be stored properly and retrieved without any issues.';

        $categoryData = [
            'category_name' => 'Detailed Category',
            'slug' => 'detailed-category',
            'description' => $longDescription,
            'parent_id' => null,
            'depth' => 0,
            'is_active' => true,
        ];

        $response = $this->postJson('/api/categories', $categoryData);

        $response->assertStatus(201)
            ->assertJson([
                'description' => $longDescription,
            ]);

        $this->assertDatabaseHas('categories', [
            'description' => $longDescription,
        ]);
    }

    public function test_category_with_null_description(): void
    {
        $this->authenticateOperator();

        $categoryData = [
            'category_name' => 'Simple Category',
            'slug' => 'simple-category',
            'description' => null,
            'parent_id' => null,
            'depth' => 0,
            'is_active' => true,
        ];

        $response = $this->postJson('/api/categories', $categoryData);

        $response->assertStatus(201)
            ->assertJson([
                'description' => null,
            ]);

        $this->assertDatabaseHas('categories', [
            'description' => null,
        ]);
    }

    public function test_category_search_by_name(): void
    {
        $this->authenticateOperator();

        Category::factory()->create(['category_name' => 'Fertilizers']);
        Category::factory()->create(['category_name' => 'Seeds']);
        Category::factory()->create(['category_name' => 'Pesticides']);

        $response = $this->getJson('/api/categories?search=Fertilizer');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonPath('0.category_name', 'Fertilizers');
    }

    public function test_category_filter_by_active_status(): void
    {
        $this->authenticateOperator();

        Category::factory()->create(['is_active' => true]);
        Category::factory()->create(['is_active' => true]);
        Category::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/categories?is_active=true');

        $response->assertStatus(200)
            ->assertJsonCount(2);

        foreach ($response->json() as $category) {
            $this->assertTrue($category['is_active']);
        }
    }

    public function test_category_filter_by_depth(): void
    {
        $this->authenticateOperator();

        Category::factory()->create(['depth' => 0]);
        Category::factory()->create(['depth' => 0]);
        Category::factory()->create(['depth' => 1]);

        $response = $this->getJson('/api/categories?depth=0');

        $response->assertStatus(200)
            ->assertJsonCount(2);

        foreach ($response->json() as $category) {
            $this->assertEquals(0, $category['depth']);
        }
    }

    public function test_category_operations_with_different_roles(): void
    {
        $category = Category::factory()->create();

        // Test with Admin role
        $admin = $this->authenticateAdmin();
        $response = $this->getJson('/api/categories');
        $response->assertStatus(200);

        // Test with Supervisor role
        $supervisor = $this->authenticateSupervisor();
        $response = $this->getJson('/api/categories');
        $response->assertStatus(200);

        // Test with Operator role
        $operator = $this->authenticateOperator();
        $response = $this->getJson('/api/categories');
        $response->assertStatus(200);
    }

    public function test_category_slug_case_insensitive_search(): void
    {
        $this->authenticateOperator();

        Category::factory()->create(['slug' => 'fertilizers']);
        Category::factory()->create(['slug' => 'seeds']);

        $response = $this->getJson('/api/categories?search=FERTILIZER');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonPath('0.slug', 'fertilizers');
    }

    public function test_category_parent_child_relationship_integrity(): void
    {
        $this->authenticateOperator();

        $parentCategory = Category::factory()->create(['depth' => 0]);
        $childCategory = Category::factory()->create(['parent_id' => $parentCategory->id, 'depth' => 1]);

        // Verify parent relationship
        $response = $this->getJson("/api/categories/{$childCategory->id}");
        $response->assertStatus(200)
            ->assertJson([
                'parent_id' => $parentCategory->id,
                'depth' => 1,
            ]);

        // Verify children relationship
        $response = $this->getJson("/api/categories/{$parentCategory->id}/children");
        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $childCategory->id);
    }
}
