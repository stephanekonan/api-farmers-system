<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Category;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    public function test_index_products_as_authenticated_user(): void
    {
        $this->authenticateOperator();
        
        Product::factory()->count(3)->create();

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'product_name',
                    'slug',
                    'description',
                    'category_id',
                    'price_fcfa',
                    'unit',
                    'is_active',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonCount(3);
    }

    public function test_index_products_without_authentication(): void
    {
        $response = $this->getJson('/api/products');

        $response->assertStatus(401);
    }

    public function test_store_product_as_authenticated_user(): void
    {
        $this->authenticateOperator();
        
        $category = Category::factory()->create();

        $productData = [
            'product_name' => 'Test Product',
            'slug' => 'test-product',
            'description' => 'This is a test product',
            'category_id' => $category->id,
            'price_fcfa' => 1500.50,
            'unit' => 'kg',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/products', $productData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'product_name',
                'slug',
                'description',
                'category_id',
                'price_fcfa',
                'unit',
                'is_active',
                'created_at',
                'updated_at',
            ]);

        $this->assertDatabaseHas('products', [
            'product_name' => 'Test Product',
            'slug' => 'test-product',
            'category_id' => $category->id,
            'price_fcfa' => 1500.50,
        ]);
    }

    public function test_store_product_without_authentication(): void
    {
        $category = Category::factory()->create();

        $productData = [
            'product_name' => 'Test Product',
            'slug' => 'test-product',
            'category_id' => $category->id,
            'price_fcfa' => 1500.50,
        ];

        $response = $this->postJson('/api/products', $productData);

        $response->assertStatus(401);
    }

    public function test_store_product_with_validation_errors(): void
    {
        $this->authenticateOperator();

        $response = $this->postJson('/api/products', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'product_name',
                'slug',
                'category_id',
                'price_fcfa',
                'unit',
            ]);
    }

    public function test_store_product_with_duplicate_slug(): void
    {
        $this->authenticateOperator();

        $existingProduct = Product::factory()->create(['slug' => 'duplicate-slug']);

        $productData = [
            'product_name' => 'Another Product',
            'slug' => 'duplicate-slug',
            'category_id' => Category::factory()->create()->id,
            'price_fcfa' => 1000.00,
            'unit' => 'kg',
        ];

        $response = $this->postJson('/api/products', $productData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_show_product_as_authenticated_user(): void
    {
        $this->authenticateOperator();
        
        $product = Product::factory()->create();

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'product_name',
                'slug',
                'description',
                'category_id',
                'price_fcfa',
                'unit',
                'is_active',
                'created_at',
                'updated_at',
            ])
            ->assertJson([
                'id' => $product->id,
                'product_name' => $product->product_name,
                'slug' => $product->slug,
            ]);
    }

    public function test_show_product_without_authentication(): void
    {
        $product = Product::factory()->create();

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(401);
    }

    public function test_show_nonexistent_product(): void
    {
        $this->authenticateOperator();

        $response = $this->getJson('/api/products/999999');

        $response->assertStatus(404);
    }

    public function test_update_product_as_authenticated_user(): void
    {
        $this->authenticateOperator();
        
        $product = Product::factory()->create();

        $updateData = [
            'product_name' => 'Updated Product',
            'description' => 'Updated description',
            'price_fcfa' => 2000.75,
        ];

        $response = $this->putJson("/api/products/{$product->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson($updateData);

        $this->assertDatabaseHas('products', array_merge(['id' => $product->id], $updateData));
    }

    public function test_update_product_without_authentication(): void
    {
        $product = Product::factory()->create();

        $response = $this->putJson("/api/products/{$product->id}", [
            'product_name' => 'Updated Product',
        ]);

        $response->assertStatus(401);
    }

    public function test_delete_product_as_authenticated_user(): void
    {
        $this->authenticateOperator();
        
        $product = Product::factory()->create();

        $response = $this->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_delete_product_without_authentication(): void
    {
        $product = Product::factory()->create();

        $response = $this->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(401);
    }

    public function test_get_products_by_category(): void
    {
        $this->authenticateOperator();
        
        $category = Category::factory()->create();
        $product1 = Product::factory()->create(['category_id' => $category->id]);
        $product2 = Product::factory()->create(['category_id' => $category->id]);
        Product::factory()->create(); // Product for different category

        $response = $this->getJson("/api/products/category/{$category->id}");

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

    public function test_get_products_by_category_without_authentication(): void
    {
        $category = Category::factory()->create();

        $response = $this->getJson("/api/products/category/{$category->id}");

        $response->assertStatus(401);
    }

    public function test_get_active_products(): void
    {
        $this->authenticateOperator();
        
        Product::factory()->create(['is_active' => true]);
        Product::factory()->create(['is_active' => true]);
        Product::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/products/active');

        $response->assertStatus(200)
            ->assertJsonCount(2);

        foreach ($response->json() as $product) {
            $this->assertTrue($product['is_active']);
        }
    }

    public function test_get_active_products_without_authentication(): void
    {
        $response = $this->getJson('/api/products/active');

        $response->assertStatus(401);
    }

    public function test_search_products(): void
    {
        $this->authenticateOperator();
        
        Product::factory()->create(['product_name' => 'Fertilizer Type A']);
        Product::factory()->create(['product_name' => 'Fertilizer Type B']);
        Product::factory()->create(['product_name' => 'Seeds Premium']);

        $response = $this->getJson('/api/products/search?q=Fertilizer');

        $response->assertStatus(200)
            ->assertJsonCount(2);

        foreach ($response->json() as $product) {
            $this->assertStringContainsString('Fertilizer', $product['product_name']);
        }
    }

    public function test_search_products_without_authentication(): void
    {
        $response = $this->getJson('/api/products/search?q=test');

        $response->assertStatus(401);
    }

    public function test_get_products_by_price_range(): void
    {
        $this->authenticateOperator();
        
        Product::factory()->create(['price_fcfa' => 500.00]);
        Product::factory()->create(['price_fcfa' => 1500.00]);
        Product::factory()->create(['price_fcfa' => 2500.00]);

        $response = $this->getJson('/api/products?min_price=1000&max_price=2000');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonPath('0.price_fcfa', 1500.00);
    }

    public function test_get_products_by_unit(): void
    {
        $this->authenticateOperator();
        
        Product::factory()->create(['unit' => 'kg']);
        Product::factory()->create(['unit' => 'kg']);
        Product::factory()->create(['unit' => 'piece']);

        $response = $this->getJson('/api/products?unit=kg');

        $response->assertStatus(200)
            ->assertJsonCount(2);

        foreach ($response->json() as $product) {
            $this->assertEquals('kg', $product['unit']);
        }
    }

    public function test_product_with_different_units(): void
    {
        $this->authenticateOperator();
        
        $kgProduct = Product::factory()->create(['unit' => 'kg', 'price_fcfa' => 1500.00]);
        $pieceProduct = Product::factory()->create(['unit' => 'piece', 'price_fcfa' => 500.00]);
        $literProduct = Product::factory()->create(['unit' => 'liter', 'price_fcfa' => 2000.00]);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonCount(3);

        $units = array_column($response->json(), 'unit');
        $this->assertContains('kg', $units);
        $this->assertContains('piece', $units);
        $this->assertContains('liter', $units);
    }

    public function test_product_price_validation(): void
    {
        $this->authenticateOperator();

        $invalidData = [
            'product_name' => 'Invalid Product',
            'slug' => 'invalid-product',
            'category_id' => Category::factory()->create()->id,
            'price_fcfa' => -100.00, // Negative price
            'unit' => 'kg',
        ];

        $response = $this->postJson('/api/products', $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['price_fcfa']);
    }

    public function test_product_with_decimal_prices(): void
    {
        $this->authenticateOperator();
        
        $productData = [
            'product_name' => 'Precision Product',
            'slug' => 'precision-product',
            'category_id' => Category::factory()->create()->id,
            'price_fcfa' => 1234.5678,
            'unit' => 'kg',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/products', $productData);

        $response->assertStatus(201)
            ->assertJson([
                'price_fcfa' => 1234.57, // Should be rounded to 2 decimal places
            ]);

        $this->assertDatabaseHas('products', [
            'price_fcfa' => 1234.57,
        ]);
    }

    public function test_product_activation_deactivation(): void
    {
        $this->authenticateOperator();
        
        $product = Product::factory()->create(['is_active' => true]);

        // Deactivate
        $response = $this->putJson("/api/products/{$product->id}", [
            'is_active' => false,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'is_active' => false,
            ]);

        // Reactivate
        $response = $this->putJson("/api/products/{$product->id}", [
            'is_active' => true,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'is_active' => true,
            ]);
    }

    public function test_product_with_long_description(): void
    {
        $this->authenticateOperator();
        
        $longDescription = 'This is a very long description that contains multiple sentences and detailed information about the product. It should be stored properly and retrieved without any issues.';
        
        $productData = [
            'product_name' => 'Detailed Product',
            'slug' => 'detailed-product',
            'description' => $longDescription,
            'category_id' => Category::factory()->create()->id,
            'price_fcfa' => 2500.00,
            'unit' => 'kg',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/products', $productData);

        $response->assertStatus(201)
            ->assertJson([
                'description' => $longDescription,
            ]);

        $this->assertDatabaseHas('products', [
            'description' => $longDescription,
        ]);
    }

    public function test_product_with_null_description(): void
    {
        $this->authenticateOperator();
        
        $productData = [
            'product_name' => 'Simple Product',
            'slug' => 'simple-product',
            'description' => null,
            'category_id' => Category::factory()->create()->id,
            'price_fcfa' => 1000.00,
            'unit' => 'piece',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/products', $productData);

        $response->assertStatus(201)
            ->assertJson([
                'description' => null,
            ]);

        $this->assertDatabaseHas('products', [
            'description' => null,
        ]);
    }

    public function test_product_category_relationship(): void
    {
        $this->authenticateOperator();
        
        $category = Category::factory()->create([
            'category_name' => 'Test Category',
            'slug' => 'test-category',
        ]);
        
        $product = Product::factory()->create(['category_id' => $category->id]);

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'category_id' => $category->id,
            ]);

        // Verify the relationship exists
        $this->assertEquals($category->id, $product->category_id);
    }

    public function test_product_operations_with_different_roles(): void
    {
        $product = Product::factory()->create();

        // Test with Admin role
        $admin = $this->authenticateAdmin();
        $response = $this->getJson('/api/products');
        $response->assertStatus(200);

        // Test with Supervisor role
        $supervisor = $this->authenticateSupervisor();
        $response = $this->getJson('/api/products');
        $response->assertStatus(200);

        // Test with Operator role
        $operator = $this->authenticateOperator();
        $response = $this->getJson('/api/products');
        $response->assertStatus(200);
    }

    public function test_product_search_by_name_case_insensitive(): void
    {
        $this->authenticateOperator();
        
        Product::factory()->create(['product_name' => 'FERTILIZER PREMIUM']);
        Product::factory()->create(['product_name' => 'fertilizer basic']);
        Product::factory()->create(['product_name' => 'Seeds Regular']);

        $response = $this->getJson('/api/products/search?q=fertilizer');

        $response->assertStatus(200)
            ->assertJsonCount(2);

        foreach ($response->json() as $product) {
            $this->assertStringContainsStringIgnoringCase('fertilizer', $product['product_name']);
        }
    }
}
