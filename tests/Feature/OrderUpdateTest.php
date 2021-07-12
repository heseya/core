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

    public const EMAIL_DATA = 'test@example.com';

    public function setUp(): void
    {
        parent::setUp();

        $this->shippingMethod = ShippingMethod::factory()->create();
        $this->status = Status::factory()->create();
        $address = Address::factory()->create();

        $this->order = Order::factory()->create([
            'code' => 'XXXXXX123',
            'email' => self::EMAIL_DATA,
            'comment' => $this->faker->text(10),
            'status_id' => $this->status->getKey(),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'delivery_address_id' => $address->getKey(),
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
        $email = $this->faker->email();
        $comment = $this->faker->text(200);
        $address = Address::factory()->create();

        $response = $this->actingAs($this->user)->patchJson('/orders/id:' . $this->order->id, [
            'code' => rand (10000, 99999) . 'ABC',
            'email' => $email,
            'comment' => $comment,
            'status_id' => $this->status->getKey(),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'delivery_address' => $address->toArray(),
            'invoice_address' => $address->toArray(),
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'email' => $email,
            'comment' => $comment,
            'delivery_address_id' => $response->getData()->data->delivery_address->id,
            'invoice_address_id' => $response->getData()->data->invoice_address->id ?? null,
        ]);
    }
}
