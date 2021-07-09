<?php

namespace Tests\Feature;

use App\Events\OrderUpdate;
use App\Models\Address;
use App\Models\Order;
use App\Models\ShippingMethod;
use App\Models\Status;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OrderUpdateTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private Order $order;
    private ShippingMethod $shippingMethod;
    private Status $status;
    private Address $address;

    public const EMAIL_DATA = 'test@example.com';

    public function setUp(): void
    {
        parent::setUp();

        $this->shippingMethod = ShippingMethod::factory()->create();
        $this->status = Status::factory()->create();
        $this->address = Address::factory()->make();

        $this->order = Order::factory()->create([
            'code' => 'XXXXXX123',
            'email' => self::EMAIL_DATA,
            'comment' => $this->faker->text(10),
            'status_id' => $this->status->getKey(),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'delivery_address_id' => $this->address->getKey(),
            'invoice_address_id' => null,
        ]);
    }

    public function testUpdateUnauthorized(): void
    {
        $response = $this->patchJson('/orders/id:' . $this->order->id);
        $response->assertUnauthorized();
    }

    public function testUpdateOrder(): void
    {
        $this->address = Address::factory()->make();
        $email = $this->faker->email();
        $comment = $this->faker->text(200);

        $response = $this->actingAs($this->user)->patchJson('/orders/id:' . $this->order->id, [
            'code' => rand (10000, 99999) . 'ABC',
            'email' => $email,
            'comment' => $comment,
            'status_id' => $this->status->getKey(),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'delivery_address' => $this->address->toArray(),
            'invoice_address' => $this->address->toArray(),
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'email' => $email,
            'comment' => $comment,
        ]);
    }
}
