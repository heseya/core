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

        $response = $this->actingAs($this->user)->patchJson('/orders/id:' . $this->order->getKey(), [
            'email' => $email,
            'comment' => $comment,
            'delivery_address' => $address->toArray(),
            'invoice_address' => $address->toArray(),
        ]);

        $responseData = $response->getData()->data;
        $response
            ->assertOk()
            ->assertJsonFragment([
                'code' => $this->order->code,
                'status' => [
                   "cancel" => false,
                    "color" => $this->status->color,
                    "description" => $this->status->description,
                    "id" => $this->status->id,
                    "name" => $this->status->name
                ],
                'delivery_address' => [
                    "address" => $responseData->delivery_address->address,
                    "city" => $responseData->delivery_address->city,
                    "country" => $responseData->delivery_address->country,
                    "country_name" => $responseData->delivery_address->country_name,
                    "id" => $responseData->delivery_address->id,
                    "name" => $responseData->delivery_address->name,
                    "phone" => $responseData->delivery_address->phone,
                    "vat" => $responseData->delivery_address->vat,
                    "zip" => $responseData->delivery_address->zip,
                ],
                'invoice_address' => [
                    "address" => $responseData->invoice_address->address,
                    "city" => $responseData->invoice_address->city,
                    "country" => $responseData->invoice_address->country,
                    "country_name" => $responseData->invoice_address->country_name,
                    "id" => $responseData->invoice_address->id,
                    "name" => $responseData->invoice_address->name,
                    "phone" => $responseData->invoice_address->phone,
                    "vat" => $responseData->invoice_address->vat,
                    "zip" => $responseData->invoice_address->zip,
                ]
            ]);


        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'email' => $email,
            'comment' => $comment,
            'delivery_address_id' => $responseData->delivery_address->id,
            'invoice_address_id' => $responseData->invoice_address->id ?? null,
        ]);
    }
}
