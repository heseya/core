<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\ShippingMethod;
use App\Models\Status;
use Laravel\Passport\Passport;
use Tests\TestCase;

class OrdersTest extends TestCase
{
    private Order $order;

    private array $expected;
    private array $expected_structure;

    public function setUp(): void
    {
        parent::setUp();

        $shippingMethod = factory(ShippingMethod::class)->create();
        $status = factory(Status::class)->create();

        $this->order = factory(Order::class)->create([
            'shipping_method_id' => $shippingMethod->getKey(),
            'status_id' => $status->getKey(),
        ]);

        /**
         * Expected response
         */
        $this->expected = [
            'code' => $this->order->code,
            'status' => [
                'id' => $status->getKey(),
                'name' => $status->name,
                'color' => $status->color,
                'description' => $status->description,
            ],
            'payed' => $this->order->isPayed(),
        ];

        $this->expected_structure = [
            'code',
            'status',
            'payed',
            'created_at',
        ];
    }

    /**
     * @return void
     */
    public function testIndex()
    {
        $response = $this->getJson('/orders');
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $response = $this->getJson('/orders');
        $response
            ->assertOk()
            ->assertJsonStructure(['data' => [
                0 => $this->expected_structure,
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
        $response = $this->getJson('/orders/' . $this->order->code);
        $response
            ->assertOk()
            ->assertJsonStructure(['data' => $this->expected_structure])
            ->assertJson(['data' => $this->expected]);
    }
}
