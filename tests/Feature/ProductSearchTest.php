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

    public function testIndex(): void
    {
        $category = Category::factory()->create(['public' => true]);

        $brand = Brand::factory()->create(['public' => true]);
        $brand2 = Brand::factory()->create(['public' => true]);

        $product = Product::factory()->create([
            'category_id' => $category->getKey(),
            'brand_id' => $brand->getKey(),
            'public' => true,
        ]);

        Product::factory()->create([
            'category_id' => $category->getKey(),
            'brand_id' => $brand2->getKey(),
            'public' => true,
        ]);

        $response = $this->getJson('/products?brand=' . $brand->slug);
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $product->getKey()]);
    }
}
