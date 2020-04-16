<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\ShippingMethod;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ShippingMethodTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->shipping_method = factory(ShippingMethod::class)->create([
            'public' => true,
        ]);

        $this->shipping_method_hidden = factory(ShippingMethod::class)->create([
            'public' => false,
        ]);

        /**
         * Expected response
         */
        $this->expected = [
            'id' => $this->shipping_method->id,
            'name' => $this->shipping_method->name,
            'price' => $this->shipping_method->price,
            'public' => $this->shipping_method->public,
        ];
    }

    /**
     * @return void
     */
    public function testIndex()
    {
        $response = $this->get('/shipping-methods');

        $response
            ->assertStatus(200)
            ->assertJsonCount(1, 'data') // Shoud show only public shipping methods.
            ->assertJson(['data' => [
                0 => $this->expected,
            ]]);
    }

    /**
     * @return void
     */
    public function testCreate()
    {
        $shipping_method = [
            'name' => 'Test',
            'price' => 1.23,
            'public' => true,
        ];

        $response = $this->post('/shipping-methods', $shipping_method);

        $response
            ->assertStatus(201)
            ->assertJson(['data' => $shipping_method]);
    }

    /**
     * @return void
     */
    public function testUpdate()
    {
        $shipping_method = [
            'name' => 'Test',
            'price' => 1.23,
            'public' => true,
        ];

        $response = $this->put(
            '/shipping-methods/id:' . $this->shipping_method->id,
            $shipping_method,
        );

        $response
            ->assertStatus(200)
            ->assertJson(['data' => $shipping_method]);
    }

    /**
     * @return void
     */
    public function testDelete()
    {
        $response = $this->delete('/shipping-methods/id:' . $this->shipping_method->id);

        $response->assertStatus(204);
    }
}
