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
    use RefreshDatabase;

    private Order $order;
    private ShippingMethod $shippingMethod;
    private Status $status;

    public function setUp(): void
    {
        parent::setUp();

        $this->shippingMethod = ShippingMethod::factory()->create();
        $this->status = Status::factory()->create();
        $address = Address::factory()->make();

        $this->order = Order::factory()->create([
            'code' => 'XXXXXX',
            'email' => 'test@example.com',
            'status_id' => $this->status->getKey(),
            'shipping_method_id' => $this->shippingMethod ->getKey(),
            'delivery_address_id' => $address->getKey(),
        ]);
    }

    public function testUpdateUnauthorized(): void
    {
        $response = $this->patchJson('/orders/id:' . $this->order->id);
        $response->assertUnauthorized();
    }

    public function testUpdateOrder(): void
    {
        $response = $this->actingAs($this->user)->patchJson('/orders/id:' . $this->order->id, [
            'code' => 'XXXXXX',
            'email' => 'test@example.com',
            'currency' => 'PLN',
            'comment' => 'test',
            'status_id' => $this->status->getKey(),
            'shipping_method_id' => $this->shippingMethod ->getKey(),
            'delivery_address' => [
                'name' => 'Zygmunt Testowy',
                'phone' => '+48123321123',
                'address' => 'Gdańska 89/1',
                'zip' => '80-432',
                'city' => 'Gdańsk',
                'country' => 'PL',
            ],
            'invoice_address' => [
                'name' => 'Iwona Testowa',
                'phone' => '+48113321121',
                'address' => 'Gdańska 89/1',
                'zip' => '80-432',
                'city' => 'Gdańsk',
                'country' => 'PL',
            ],
        ]);

        $response->assertOk();
    }
}
