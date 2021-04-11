<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;
    private Brand $brand;

    public function setUp(): void
    {
        parent::setUp();

        $this->category = Category::factory()->create(['public' => true]);
        $this->brand = Brand::factory()->create(['public' => true]);
    }

    public function testSearch(): void
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->getKey(),
            'brand_id' => $this->brand->getKey(),
            'public' => true,
        ]);

        $product2 = Product::factory()->create([
            'category_id' => $this->category->getKey(),
            'brand_id' => $this->brand->getKey(),
            'public' => true,
        ]);

        $response = $this->getJson('/products?search=' . $product->category->name);
        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $product->getKey()])
            ->assertJsonFragment(['id' => $product2->getKey()]);

        $response = $this->getJson('/products?search=' . $product->name);
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $product->getKey()]);
    }

    public function testSearchByBrand(): void
    {
        $brand = Brand::factory()->create(['public' => true]);

        $product = Product::factory()->create([
            'category_id' => $this->category->getKey(),
            'brand_id' => $brand->getKey(),
            'public' => true,
        ]);

        Product::factory()->create([
            'category_id' => $this->category->getKey(),
            'brand_id' => $this->brand->getKey(),
            'public' => true,
        ]);

        $response = $this->getJson('/products?brand=' . $brand->slug);
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $product->getKey()]);
    }

    public function testSearchByCategory(): void
    {
        $category = Category::factory()->create(['public' => true]);

        $product = Product::factory()->create([
            'category_id' => $category->getKey(),
            'brand_id' => $this->brand->getKey(),
            'public' => true,
        ]);

        Product::factory()->create([
            'category_id' => $this->category->getKey(),
            'brand_id' => $this->brand->getKey(),
            'public' => true,
        ]);

        $response = $this->getJson('/products?category=' . $category->slug);
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $product->getKey()]);
    }
}
