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

        $brand = factory(Brand::class)->create(['public' => true]);
        $category = factory(Category::class)->create(['public' => true]);

        $this->product = factory(Product::class)->create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'public' => true,
        ]);

        // Hidden
        $brand_hidden = factory(Brand::class)->create(['public' => false]);
        $category_hidden = factory(Category::class)->create(['public' => false]);

        $this->hidden_products = [
            factory(Product::class)->create([
                'brand_id' => $brand->id,
                'category_id' => $category->id,
                'public' => false,
            ]),
            factory(Product::class)->create([
                'brand_id' => $brand_hidden->id,
                'category_id' => $category->id,
                'public' => true,
            ]),
            factory(Product::class)->create([
                'brand_id' => $brand->id,
                'category_id' => $category_hidden->id,
                'public' => true,
            ]),
            factory(Product::class)->create([
                'brand_id' => $brand_hidden->id,
                'category_id' => $category_hidden->id,
                'public' => true,
            ]),
            factory(Product::class)->create([
                'brand_id' => $brand_hidden->id,
                'category_id' => $category->id,
                'public' => false,
            ]),
            factory(Product::class)->create([
                'brand_id' => $brand->id,
                'category_id' => $category_hidden->id,
                'public' => false,
            ]),
            factory(Product::class)->create([
                'brand_id' => $brand_hidden->id,
                'category_id' => $category_hidden->id,
                'public' => false,
            ]),
        ];

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
            ->assertJsonCount(1, 'data') // Shoud show only public products.
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

    /**
     * @return void
     */
    public function testViewHidden()
    {
        foreach ($this->hidden_products as $product) {
            $response = $this->get('/products/' . $product->slug);

            $response
                ->assertStatus(401)
                ->assertJsonStructure(['error' => [
                    'code',
                    'message',
                ]]);
        }
    }
}
