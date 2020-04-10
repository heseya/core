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

        $response->assertStatus(200);
    }

    /**
     * @return void
     */
    public function testView()
    {
        $response = $this->get('/products/' . $this->product->slug);

        $response->assertStatus(200);
    }
}
