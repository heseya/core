<?php

namespace Tests\Feature;

use App\Product;
use Tests\TestCase;

class ProductsApiTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->product = factory(Product::class)->create();
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
