<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\Category;
use App\Models\TransactionItem;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_can_be_created_with_factory(): void
    {
        $product = Product::factory()->create();

        $this->assertInstanceOf(Product::class, $product);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'product_name' => $product->product_name,
            'slug' => $product->slug,
            'category_id' => $product->category_id,
        ]);
    }

    public function test_product_fillable_attributes(): void
    {
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

        $product = Product::create($productData);

        $this->assertEquals('Test Product', $product->product_name);
        $this->assertEquals('test-product', $product->slug);
        $this->assertEquals('This is a test product', $product->description);
        $this->assertEquals($category->id, $product->category_id);
        $this->assertEquals(1500.50, $product->price_fcfa);
        $this->assertEquals('kg', $product->unit);
        $this->assertTrue($product->is_active);
    }

    public function test_price_fcfa_casting(): void
    {
        $product = Product::factory()->create(['price_fcfa' => '1500.75']);

        $this->assertIsFloat($product->price_fcfa);
        $this->assertEquals(1500.75, $product->price_fcfa);
    }

    public function test_is_active_casting(): void
    {
        $product = Product::factory()->create(['is_active' => true]);

        $this->assertIsBool($product->is_active);
        $this->assertTrue($product->is_active);
    }

    public function test_product_uses_soft_deletes(): void
    {
        $product = Product::factory()->create();
        $productId = $product->id;

        $product->delete();

        $this->assertSoftDeleted('products', ['id' => $productId]);
        $this->assertNotNull($product->deleted_at);
    }

    public function test_category_relationship(): void
    {
        $product = Product::factory()->create();
        $this->assertInstanceOf(BelongsTo::class, $product->category());
    }

    public function test_category_relationship_can_be_populated(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $relatedCategory = $product->category;

        $this->assertInstanceOf(Category::class, $relatedCategory);
        $this->assertEquals($category->id, $relatedCategory->id);
    }

    public function test_transaction_items_relationship(): void
    {
        $product = Product::factory()->create();
        $this->assertInstanceOf(HasMany::class, $product->transactionItems());
    }

    public function test_transaction_items_relationship_can_be_populated(): void
    {
        $product = Product::factory()->create();
        $item1 = TransactionItem::factory()->create(['product_id' => $product->id]);
        $item2 = TransactionItem::factory()->create(['product_id' => $product->id]);

        $items = $product->transactionItems;

        $this->assertCount(2, $items);
        $this->assertContains($item1->id, $items->pluck('id'));
        $this->assertContains($item2->id, $items->pluck('id'));
    }

    public function test_slug_is_unique(): void
    {
        $slug = 'unique-slug';
        
        Product::factory()->create(['slug' => $slug]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Product::factory()->create(['slug' => $slug]);
    }

    public function test_product_can_be_marked_inactive(): void
    {
        $product = Product::factory()->create(['is_active' => true]);

        $product->update(['is_active' => false]);

        $this->assertFalse($product->is_active);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'is_active' => false,
        ]);
    }

    public function test_product_can_be_marked_active(): void
    {
        $product = Product::factory()->create(['is_active' => false]);

        $product->update(['is_active' => true]);

        $this->assertTrue($product->is_active);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'is_active' => true,
        ]);
    }

    public function test_product_price_can_be_updated(): void
    {
        $product = Product::factory()->create(['price_fcfa' => 1000.00]);

        $product->update(['price_fcfa' => 1500.75]);

        $this->assertEquals(1500.75, $product->price_fcfa);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'price_fcfa' => 1500.75,
        ]);
    }

    public function test_product_name_can_be_updated(): void
    {
        $product = Product::factory()->create(['product_name' => 'Old Name']);

        $product->update(['product_name' => 'New Name']);

        $this->assertEquals('New Name', $product->product_name);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'product_name' => 'New Name',
        ]);
    }

    public function test_product_description_can_be_updated(): void
    {
        $product = Product::factory()->create(['description' => 'Old description']);

        $product->update(['description' => 'New description']);

        $this->assertEquals('New description', $product->description);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'description' => 'New description',
        ]);
    }

    public function test_product_unit_can_be_updated(): void
    {
        $product = Product::factory()->create(['unit' => 'kg']);

        $product->update(['unit' => 'piece']);

        $this->assertEquals('piece', $product->unit);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'unit' => 'piece',
        ]);
    }

    public function test_product_can_be_queried_by_active_status(): void
    {
        $activeProduct = Product::factory()->create(['is_active' => true]);
        $inactiveProduct = Product::factory()->create(['is_active' => false]);

        $activeProducts = Product::where('is_active', true)->get();
        $inactiveProducts = Product::where('is_active', false)->get();

        $this->assertCount(1, $activeProducts);
        $this->assertCount(1, $inactiveProducts);
        $this->assertEquals($activeProduct->id, $activeProducts->first()->id);
        $this->assertEquals($inactiveProduct->id, $inactiveProducts->first()->id);
    }

    public function test_product_can_be_queried_by_category(): void
    {
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();

        $product1 = Product::factory()->create(['category_id' => $category1->id]);
        $product2 = Product::factory()->create(['category_id' => $category1->id]);
        $product3 = Product::factory()->create(['category_id' => $category2->id]);

        $category1Products = Product::where('category_id', $category1->id)->get();
        $category2Products = Product::where('category_id', $category2->id)->get();

        $this->assertCount(2, $category1Products);
        $this->assertCount(1, $category2Products);
        $this->assertContains($product1->id, $category1Products->pluck('id'));
        $this->assertContains($product2->id, $category1Products->pluck('id'));
        $this->assertEquals($product3->id, $category2Products->first()->id);
    }

    public function test_product_can_be_queried_by_price_range(): void
    {
        $product1 = Product::factory()->create(['price_fcfa' => 500.00]);
        $product2 = Product::factory()->create(['price_fcfa' => 1500.00]);
        $product3 = Product::factory()->create(['price_fcfa' => 2500.00]);

        $lowPriceProducts = Product::where('price_fcfa', '<=', 1500.00)->get();
        $highPriceProducts = Product::where('price_fcfa', '>', 1500.00)->get();

        $this->assertCount(2, $lowPriceProducts);
        $this->assertCount(1, $highPriceProducts);
        $this->assertContains($product1->id, $lowPriceProducts->pluck('id'));
        $this->assertContains($product2->id, $lowPriceProducts->pluck('id'));
        $this->assertEquals($product3->id, $highPriceProducts->first()->id);
    }

    public function test_product_can_be_queried_by_unit(): void
    {
        $kgProduct = Product::factory()->create(['unit' => 'kg']);
        $pieceProduct = Product::factory()->create(['unit' => 'piece']);
        $literProduct = Product::factory()->create(['unit' => 'liter']);

        $kgProducts = Product::where('unit', 'kg')->get();
        $pieceProducts = Product::where('unit', 'piece')->get();
        $literProducts = Product::where('unit', 'liter')->get();

        $this->assertCount(1, $kgProducts);
        $this->assertCount(1, $pieceProducts);
        $this->assertCount(1, $literProducts);
        $this->assertEquals($kgProduct->id, $kgProducts->first()->id);
        $this->assertEquals($pieceProduct->id, $pieceProducts->first()->id);
        $this->assertEquals($literProduct->id, $literProducts->first()->id);
    }

    public function test_product_can_be_queried_by_name(): void
    {
        $product1 = Product::factory()->create(['product_name' => 'Fertilizer']);
        $product2 = Product::factory()->create(['product_name' => 'Seeds']);
        $product3 = Product::factory()->create(['product_name' => 'Pesticide']);

        $fertilizerProducts = Product::where('product_name', 'Fertilizer')->get();
        $seedsProducts = Product::where('product_name', 'Seeds')->get();

        $this->assertCount(1, $fertilizerProducts);
        $this->assertCount(1, $seedsProducts);
        $this->assertEquals($product1->id, $fertilizerProducts->first()->id);
        $this->assertEquals($product2->id, $seedsProducts->first()->id);
    }

    public function test_product_can_be_queried_by_slug(): void
    {
        $product1 = Product::factory()->create(['slug' => 'fertilizer']);
        $product2 = Product::factory()->create(['slug' => 'seeds']);

        $foundProduct1 = Product::where('slug', 'fertilizer')->first();
        $foundProduct2 = Product::where('slug', 'seeds')->first();

        $this->assertEquals($product1->id, $foundProduct1->id);
        $this->assertEquals($product2->id, $foundProduct2->id);
    }
}
