<?php

namespace Tests\Feature;

use App\Order;
use Tests\TestCase;
use App\ShippingMethod;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrdersTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $shippingMethod = factory(ShippingMethod::class)->create();

        $this->order = factory(Order::class)->create([
            'shipping_method_id' => $shippingMethod->id,
        ]);

        /**
         * Expected response
         */
        $this->expected = [
            'code' => $this->order->code,
            'payment_status' => $this->order->payment_status,
            'shop_status' => $this->order->shop_status,
            'delivery_status' => $this->order->delivery_status,
        ];
    }

    /**
     * @return void
     */
    public function testIndex()
    {
        $response = $this->get('/orders');

        $response
            ->assertStatus(200)
            ->assertJson(['data' => [
                0 => $this->expected,
            ]]);
    }

    /**
     * @return void
     */
    public function testViewPublic()
    {
        $response = $this->get('/orders/' . $this->order->code);

        $response
            ->assertStatus(200)
            ->assertJson(['data' => $this->expected]);
    }
}
