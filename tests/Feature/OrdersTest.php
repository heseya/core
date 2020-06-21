<?php

namespace Tests\Feature;

use App\Models\Order;
use Tests\TestCase;
use App\Models\ShippingMethod;
use Laravel\Passport\Passport;
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
            'status' => [
                'id' => $this->order->status->id,
                'name' => $this->order->status->name,
                'color' => $this->order->status->color,
                'description' => $this->order->status->description,
            ],
        ];

        $this->expectedStructure = [
            'code',
            'status',
            'created_at',
        ];
    }

    /**
     * @return void
     */
    public function testIndex()
    {
        $response = $this->get('/orders');
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $response = $this->get('/orders');
        $response
            ->assertOk()
            ->assertJsonStructure(['data' => [
                0 => $this->expectedStructure
            ]])
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
            ->assertOk()
            ->assertJsonStructure(['data' => $this->expectedStructure])
            ->assertJson(['data' => $this->expected]);
    }
}
