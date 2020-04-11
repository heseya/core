<?php

namespace Tests\Feature;

use App\Brand;
use App\Product;
use App\Category;
use Tests\TestCase;

class ProductsApiTest extends TestCase
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
    }

    /**
     * @return void
     */
    public function testIndex()
    {
        $response = $this->get('/products');

        $response->assertStatus(200)->assertJsonStructure(['data']);
    }

    /**
     * @return void
     */
    public function testView()
    {
        $response = $this->get('/products/' . $this->product->slug);

        $response->assertStatus(200);

        $response->assertExactJson(['data' => [
            'id' => $this->product->id,
            'name' => $this->product->name,
            'slug' => $this->product->slug,
            'price' => $this->product->price,
            'description' => $this->product->description,
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
            'gallery' => [],
            'schemas' => [],
        ]]);
    }
}
