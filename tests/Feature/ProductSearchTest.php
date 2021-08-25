<?php

namespace Tests\Feature;

use App\Models\ProductSet;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    private ProductSet $category;
    private ProductSet $brand;

    public function setUp(): void
    {
        parent::setUp();

        $this->category = ProductSet::factory()->create([
            'public' => true,
            'hide_on_index' => false,
        ]);

        $this->brand = ProductSet::factory()->create([
            'public' => true,
            'hide_on_index' => false,
        ]);

        $this->brand = ProductSet::factory()->create([
            'public' => true,
            'hide_on_index' => false,
        ]);
    }

    public function testSearch(): void
    {
        $this->user->givePermissionTo('products.show');

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

        $response = $this->actingAs($this->user)
            ->getJson('/products?search=' . $product->category->name);
        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $product->getKey()])
            ->assertJsonFragment(['id' => $product2->getKey()]);

        $response = $this->actingAs($this->user)
            ->getJson('/products?search=' . $product->name);
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $product->getKey()]);
    }

    public function testSearchByBrand(): void
    {
        $this->user->givePermissionTo('products.show');

        $brand = ProductSet::factory()->create([
            'public' => true,
            'hide_on_index' => false,
        ]);

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

        $response = $this->actingAs($this->user)
            ->getJson('/products?brand=' . $brand->slug);
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $product->getKey()]);
    }

    public function testSearchByCategory(): void
    {
        $this->user->givePermissionTo('products.show');

        $category = ProductSet::factory()->create([
            'public' => true,
            'hide_on_index' => false,
        ]);

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

        $response = $this->actingAs($this->user)
            ->getJson('/products?category=' . $category->slug);
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $product->getKey()]);
    }

    public function testSearchBySet(): void
    {
        $this->user->givePermissionTo('products.show');

        $set = ProductSet::factory()->create([
            'public' => true,
            'hide_on_index' => false,
        ]);

        $product = Product::factory()->create([
            'public' => true,
        ]);

        $set->products()->attach($product);

        Product::factory()->create([
            'category_id' => $this->category->getKey(),
            'brand_id' => $this->brand->getKey(),
            'public' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/products?set=' . $set->slug);
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $product->getKey()]);
    }
}
