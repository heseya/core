<?php

namespace Tests\Feature;

use App\Brand;
use App\Product;
use App\Category;
use Tests\TestCase;

class ProductsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $brand = factory(Brand::class)->create();
        $category = factory(Category::class)->create();

        $this->product = factory(Product::class)->create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
        ]);

        /**
         * Expected short response
         */
        $this->expected_short = [
            'id' => $this->product->id,
            'name' => $this->product->name,
            'slug' => $this->product->slug,
            'price' => $this->product->price,
            'public' => (bool) $this->product->public,
            'brand' => [
                'id' => $this->product->brand->id,
                'name' => $this->product->brand->name,
                'slug' => $this->product->brand->slug,
                'public' => (bool) $this->product->brand->public,
            ],
            'category' => [
                'id' => $this->product->category->id,
                'name' => $this->product->category->name,
                'slug' => $this->product->category->slug,
                'public' => (bool) $this->product->category->public,
            ],
            'cover' => null,
        ];

        /**
         * Expected full response
         */
        $this->expected = array_merge($this->expected_short, [
            'description' => $this->product->description,
            'gallery' => [],
            'schemas' => [],
        ]);
    }

    /**
     * @return void
     */
    public function testIndex()
    {
        $response = $this->get('/products');

        $response
            ->assertStatus(200)
            ->assertJson(['data' => [
                0 => $this->expected_short,
            ]]);
    }

    /**
     * @return void
     */
    public function testView()
    {
        $response = $this->get('/products/' . $this->product->slug);

        $response
            ->assertStatus(200)
            ->assertExactJson(['data' => $this->expected]);
    }
}
