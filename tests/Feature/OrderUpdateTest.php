<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Order;
use App\Models\Status;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Traits\CreateShippingMethod;

class OrderUpdateTest extends TestCase
{
    use RefreshDatabase, WithFaker, CreateShippingMethod;

    private Order $order;
    private string $comment;
    private Status $status;
    private Address $addressDelivery;
    private Address $addressInvoice;

    public const EMAIL = 'test@example.com';

    public function setUp(): void
    {
        parent::setUp();

        $shippingMethod = $this->createShippingMethod();
        $this->comment = $this->faker->text(10);
        $this->status = Status::factory()->create();
        $this->addressDelivery = Address::factory()->create();
        $this->addressInvoice = Address::factory()->create();

        $this->order = Order::factory()->create([
            'code' => 'XXXXXX123',
            'email' => self::EMAIL,
            'comment' => $this->comment,
            'status_id' => $this->status->getKey(),
            'shipping_method_id' => $shippingMethod->getKey(),
            'delivery_address_id' => $this->addressDelivery->getKey(),
            'invoice_address_id' => $this->addressInvoice->getKey(),
        ]);
    }

    public function testUpdateUnauthorized(): void
    {
        $response = $this->patchJson('/orders/id:' . $this->order->id);
        $response->assertUnauthorized();
    }

    public function testFullUpdateOrder(): void
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
                    "id" => $responseData->delivery_address->id,
                    "address" => $address->address,
                    "city" => $address->city,
                    "country" => $address->country ?? null,
                    "country_name" => $responseData->delivery_address->country_name,
                    "name" => $address->name,
                    "phone" => $address->phone,
                    "vat" => $address->vat,
                    "zip" => $address->zip,
                ],
                'invoice_address' => [
                    "id" => $responseData->invoice_address->id,
                    "address" => $address->address,
                    "city" => $address->city,
                    "country" => $address->country,
                    "country_name" => $responseData->invoice_address->country_name,
                    "name" => $address->name,
                    "phone" => $address->phone,
                    "vat" => $address->vat,
                    "zip" => $address->zip,
                ]
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->getKey(),
            'email' => $email,
            'comment' => $comment,
            'delivery_address_id' => $responseData->delivery_address->id,
            'invoice_address_id' => $responseData->invoice_address->id,
        ]);
    }

    public function testUpdateOrderByEmail(): void
    {
        $email = $this->faker->email();
        $response = $this->actingAs($this->user)->patchJson('/orders/id:' . $this->order->getKey(), [
            'email' => $email
        ]);

        $response
            ->assertOk()
            ->assertJsonFragment([
             'email' => $email,
             'code' => $this->order->code,
             'comment' => $this->order->comment,
             'status' => [
                 "cancel" => false,
                 "color" => $this->status->color,
                 "description" => $this->status->description,
                 "id" => $this->status->getKey(),
                 "name" => $this->status->name
             ],
         ]);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->getKey(),
            'email' => $email,

            // should remain the same
            'comment' => $this->comment,
            'delivery_address_id' => $this->addressDelivery->getKey(),
            'invoice_address_id' => $this->addressInvoice->getKey(),
        ]);
    }

    public function testUpdateOrderByComment(): void
    {
        $comment = $this->faker->text(100);
        $response = $this->actingAs($this->user)->patchJson('/orders/id:' . $this->order->getKey(), [
            'comment' => $comment
        ]);

        $response
            ->assertOk()
            ->assertJsonFragment([
                 'email' => $this->order->email,
                 'code' => $this->order->code,
                 'comment' => $comment,
                 'status' => [
                     "cancel" => false,
                     "color" => $this->status->color,
                     "description" => $this->status->description,
                     "id" => $this->status->getKey(),
                     "name" => $this->status->name
                 ],
             ]);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->getKey(),
            'comment' => $comment,

            // should remain the same
            'email' => self::EMAIL,
            'delivery_address_id' => $this->addressDelivery->getKey(),
            'invoice_address_id' => $this->addressInvoice->getKey(),
        ]);
    }

    public function testUpdateOrderByDeliveryAddress(): void
    {
        $address = Address::factory()->create();
        $response = $this->actingAs($this->user)->patchJson('/orders/id:' . $this->order->getKey(), [
            'delivery_address' => $address->toArray()
        ]);

        $responseData = $response->getData()->data;
        $response
            ->assertOk()
            ->assertJsonFragment([
                 'email' => $this->order->email,
                 'code' => $this->order->code,
                 'comment' => $this->order->comment,
                 'status' => [
                     "cancel" => false,
                     "color" => $this->status->color,
                     "description" => $this->status->description,
                     "id" => $this->status->getKey(),
                     "name" => $this->status->name
                 ],
                 'delivery_address' => [
                     "address" => $address->address,
                     "city" => $address->city,
                     "country" => $address->country ?? null,
                     "country_name" => $responseData->delivery_address->country_name,
                     "id" => $responseData->delivery_address->id,
                     "name" => $address->name,
                     "phone" => $address->phone,
                     "vat" => $address->vat,
                     "zip" => $address->zip,
                 ],
             ]);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->getKey(),
            'delivery_address_id' => $responseData->delivery_address->id,

            // should remain the same
            'email' => self::EMAIL,
            'comment' => $this->comment,
            'invoice_address_id' => $this->addressInvoice->getKey(),
        ]);
    }

    public function testUpdateOrderByInvoiceAddress(): void
    {
        $address = Address::factory()->create();
        $response = $this->actingAs($this->user)->patchJson('/orders/id:' . $this->order->getKey(), [
            'invoice_address' => $address->toArray()
        ]);

        $responseData = $response->getData()->data;
        $response
            ->assertOk()
            ->assertJsonFragment([
                 'email' => $this->order->email,
                 'code' => $this->order->code,
                 'comment' => $this->order->comment,
                 'status' => [
                     "cancel" => false,
                     "color" => $this->status->color,
                     "description" => $this->status->description,
                     "id" => $this->status->getKey(),
                     "name" => $this->status->name
                 ],
                 'invoice_address' => [
                     "address" => $address->address,
                     "city" => $address->city,
                     "country" => $address->country ?? null,
                     "country_name" => $responseData->invoice_address->country_name,
                     "id" => $responseData->invoice_address->id,
                     "name" => $address->name,
                     "phone" => $address->phone,
                     "vat" => $address->vat,
                     "zip" => $address->zip,
                 ],
             ]);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->getKey(),
            'invoice_address_id' => $responseData->invoice_address->id,

            // should remain the same
            'email' => self::EMAIL,
            'comment' => $this->comment,
            'delivery_address_id' => $this->addressDelivery->getKey(),
        ]);
    }
}
