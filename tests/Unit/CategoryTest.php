<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_can_be_created_with_factory(): void
    {
        $category = Category::factory()->create();

        $this->assertInstanceOf(Category::class, $category);
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'category_name' => $category->category_name,
            'slug' => $category->slug,
        ]);
    }

    public function test_category_fillable_attributes(): void
    {
        $categoryData = [
            'category_name' => 'Test Category',
            'slug' => 'test-category',
            'description' => 'This is a test category',
            'parent_id' => null,
            'depth' => 0,
            'is_active' => true,
        ];

        $category = Category::create($categoryData);

        $this->assertEquals('Test Category', $category->category_name);
        $this->assertEquals('test-category', $category->slug);
        $this->assertEquals('This is a test category', $category->description);
        $this->assertNull($category->parent_id);
        $this->assertEquals(0, $category->depth);
        $this->assertTrue($category->is_active);
    }

    public function test_is_active_casting(): void
    {
        $category = Category::factory()->create(['is_active' => true]);

        $this->assertIsBool($category->is_active);
        $this->assertTrue($category->is_active);
    }

    public function test_parent_relationship(): void
    {
        $category = Category::factory()->create();
        $this->assertInstanceOf(BelongsTo::class, $category->parent());
    }

    public function test_parent_relationship_can_be_populated(): void
    {
        $parentCategory = Category::factory()->create();
        $childCategory = Category::factory()->create(['parent_id' => $parentCategory->id]);

        $relatedParent = $childCategory->parent;

        $this->assertInstanceOf(Category::class, $relatedParent);
        $this->assertEquals($parentCategory->id, $relatedParent->id);
    }

    public function test_parent_relationship_can_be_null(): void
    {
        $rootCategory = Category::factory()->create(['parent_id' => null]);

        $parent = $rootCategory->parent;

        $this->assertNull($parent);
    }

    public function test_children_relationship(): void
    {
        $category = Category::factory()->create();
        $this->assertInstanceOf(HasMany::class, $category->children());
    }

    public function test_children_relationship_can_be_populated(): void
    {
        $parentCategory = Category::factory()->create();
        $child1 = Category::factory()->create(['parent_id' => $parentCategory->id]);
        $child2 = Category::factory()->create(['parent_id' => $parentCategory->id]);

        $children = $parentCategory->children;

        $this->assertCount(2, $children);
        $this->assertContains($child1->id, $children->pluck('id'));
        $this->assertContains($child2->id, $children->pluck('id'));
    }

    public function test_products_relationship(): void
    {
        $category = Category::factory()->create();
        $this->assertInstanceOf(HasMany::class, $category->products());
    }

    public function test_products_relationship_can_be_populated(): void
    {
        $category = Category::factory()->create();
        $product1 = Product::factory()->create(['category_id' => $category->id]);
        $product2 = Product::factory()->create(['category_id' => $category->id]);

        $products = $category->products;

        $this->assertCount(2, $products);
        $this->assertContains($product1->id, $products->pluck('id'));
        $this->assertContains($product2->id, $products->pluck('id'));
    }

    public function test_slug_is_unique(): void
    {
        $slug = 'unique-category-slug';
        
        Category::factory()->create(['slug' => $slug]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Category::factory()->create(['slug' => $slug]);
    }

    public function test_category_can_be_marked_inactive(): void
    {
        $category = Category::factory()->create(['is_active' => true]);

        $category->update(['is_active' => false]);

        $this->assertFalse($category->is_active);
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'is_active' => false,
        ]);
    }

    public function test_category_can_be_marked_active(): void
    {
        $category = Category::factory()->create(['is_active' => false]);

        $category->update(['is_active' => true]);

        $this->assertTrue($category->is_active);
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'is_active' => true,
        ]);
    }

    public function test_category_name_can_be_updated(): void
    {
        $category = Category::factory()->create(['category_name' => 'Old Name']);

        $category->update(['category_name' => 'New Name']);

        $this->assertEquals('New Name', $category->category_name);
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'category_name' => 'New Name',
        ]);
    }

    public function test_category_description_can_be_updated(): void
    {
        $category = Category::factory()->create(['description' => 'Old description']);

        $category->update(['description' => 'New description']);

        $this->assertEquals('New description', $category->description);
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'description' => 'New description',
        ]);
    }

    public function test_category_depth_can_be_updated(): void
    {
        $category = Category::factory()->create(['depth' => 0]);

        $category->update(['depth' => 1]);

        $this->assertEquals(1, $category->depth);
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'depth' => 1,
        ]);
    }

    public function test_category_parent_can_be_updated(): void
    {
        $parentCategory = Category::factory()->create();
        $childCategory = Category::factory()->create(['parent_id' => null]);

        $childCategory->update(['parent_id' => $parentCategory->id]);

        $this->assertEquals($parentCategory->id, $childCategory->parent_id);
        $this->assertDatabaseHas('categories', [
            'id' => $childCategory->id,
            'parent_id' => $parentCategory->id,
        ]);
    }

    public function test_category_can_be_queried_by_active_status(): void
    {
        $activeCategory = Category::factory()->create(['is_active' => true]);
        $inactiveCategory = Category::factory()->create(['is_active' => false]);

        $activeCategories = Category::where('is_active', true)->get();
        $inactiveCategories = Category::where('is_active', false)->get();

        $this->assertCount(1, $activeCategories);
        $this->assertCount(1, $inactiveCategories);
        $this->assertEquals($activeCategory->id, $activeCategories->first()->id);
        $this->assertEquals($inactiveCategory->id, $inactiveCategories->first()->id);
    }

    public function test_category_can_be_queried_by_parent(): void
    {
        $parentCategory = Category::factory()->create();
        $childCategory1 = Category::factory()->create(['parent_id' => $parentCategory->id]);
        $childCategory2 = Category::factory()->create(['parent_id' => $parentCategory->id]);
        $rootCategory = Category::factory()->create(['parent_id' => null]);

        $childCategories = Category::where('parent_id', $parentCategory->id)->get();
        $rootCategories = Category::whereNull('parent_id')->get();

        $this->assertCount(2, $childCategories);
        $this->assertCount(1, $rootCategories);
        $this->assertContains($childCategory1->id, $childCategories->pluck('id'));
        $this->assertContains($childCategory2->id, $childCategories->pluck('id'));
        $this->assertEquals($rootCategory->id, $rootCategories->first()->id);
    }

    public function test_category_can_be_queried_by_depth(): void
    {
        $rootCategory = Category::factory()->create(['depth' => 0]);
        $childCategory = Category::factory()->create(['depth' => 1]);
        $grandchildCategory = Category::factory()->create(['depth' => 2]);

        $rootCategories = Category::where('depth', 0)->get();
        $childCategories = Category::where('depth', 1)->get();
        $grandchildCategories = Category::where('depth', 2)->get();

        $this->assertCount(1, $rootCategories);
        $this->assertCount(1, $childCategories);
        $this->assertCount(1, $grandchildCategories);
        $this->assertEquals($rootCategory->id, $rootCategories->first()->id);
        $this->assertEquals($childCategory->id, $childCategories->first()->id);
        $this->assertEquals($grandchildCategory->id, $grandchildCategories->first()->id);
    }

    public function test_category_can_be_queried_by_name(): void
    {
        $category1 = Category::factory()->create(['category_name' => 'Fertilizers']);
        $category2 = Category::factory()->create(['category_name' => 'Seeds']);
        $category3 = Category::factory()->create(['category_name' => 'Pesticides']);

        $fertilizerCategories = Category::where('category_name', 'Fertilizers')->get();
        $seedsCategories = Category::where('category_name', 'Seeds')->get();

        $this->assertCount(1, $fertilizerCategories);
        $this->assertCount(1, $seedsCategories);
        $this->assertEquals($category1->id, $fertilizerCategories->first()->id);
        $this->assertEquals($category2->id, $seedsCategories->first()->id);
    }

    public function test_category_can_be_queried_by_slug(): void
    {
        $category1 = Category::factory()->create(['slug' => 'fertilizers']);
        $category2 = Category::factory()->create(['slug' => 'seeds']);

        $foundCategory1 = Category::where('slug', 'fertilizers')->first();
        $foundCategory2 = Category::where('slug', 'seeds')->first();

        $this->assertEquals($category1->id, $foundCategory1->id);
        $this->assertEquals($category2->id, $foundCategory2->id);
    }

    public function test_hierarchical_relationships(): void
    {
        $rootCategory = Category::factory()->create(['parent_id' => null, 'depth' => 0]);
        $childCategory = Category::factory()->create(['parent_id' => $rootCategory->id, 'depth' => 1]);
        $grandchildCategory = Category::factory()->create(['parent_id' => $childCategory->id, 'depth' => 2]);

        // Test parent relationships
        $this->assertEquals($rootCategory->id, $childCategory->parent->id);
        $this->assertEquals($childCategory->id, $grandchildCategory->parent->id);
        $this->assertNull($rootCategory->parent);

        // Test children relationships
        $this->assertCount(1, $rootCategory->children);
        $this->assertCount(1, $childCategory->children);
        $this->assertCount(0, $grandchildCategory->children);
        $this->assertEquals($childCategory->id, $rootCategory->children->first()->id);
        $this->assertEquals($grandchildCategory->id, $childCategory->children->first()->id);
    }

    public function test_category_with_products_and_children(): void
    {
        $parentCategory = Category::factory()->create();
        $childCategory = Category::factory()->create(['parent_id' => $parentCategory->id]);
        
        $product1 = Product::factory()->create(['category_id' => $parentCategory->id]);
        $product2 = Product::factory()->create(['category_id' => $parentCategory->id]);
        $product3 = Product::factory()->create(['category_id' => $childCategory->id]);

        $parentProducts = $parentCategory->products;
        $childProducts = $childCategory->products;
        $children = $parentCategory->children;

        $this->assertCount(2, $parentProducts);
        $this->assertCount(1, $childProducts);
        $this->assertCount(1, $children);
        $this->assertContains($product1->id, $parentProducts->pluck('id'));
        $this->assertContains($product2->id, $parentProducts->pluck('id'));
        $this->assertEquals($product3->id, $childProducts->first()->id);
        $this->assertEquals($childCategory->id, $children->first()->id);
    }
}
