<?php

namespace Tests\Feature;

use App\Events\OrderStatusUpdated;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\Status;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class OrderTest extends TestCase
{
    private Order $order;

    private array $expected;
    private array $expected_structure;

    public function setUp(): void
    {
        parent::setUp();

        Product::factory()->create();

        $shipping_method = ShippingMethod::factory()->create();
        $status = Status::factory()->create();
        $product = Product::factory()->create();

        $this->order = Order::factory()->create([
            'shipping_method_id' => $shipping_method->getKey(),
            'status_id' => $status->getKey(),
        ]);

        $item = $this->order->products()->create([
            'product_id' => $product->getKey(),
            'quantity' => 10,
            'price' => 247.47,
        ]);

        $item->schemas()->create([
            'name' => 'Grawer',
            'value' => 'HESEYA',
            'price' => 49.99,
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

    public function testIndex(): void
    {
        $response = $this->getJson('/orders');
        $response->assertUnauthorized();

        $response = $this->actingAs($this->user)->getJson('/orders');
        $response
            ->assertOk()
            ->assertJsonStructure(['data' => [
                0 => $this->expected_structure,
            ]])
            ->assertJson(['data' => [
                0 => $this->expected,
            ]]);
    }

    public function testViewPublic(): void
    {
        $response = $this->getJson('/orders/' . $this->order->code);
        $response
            ->assertOk()
            ->assertJsonStructure(['data' => $this->expected_structure])
            ->assertJson(['data' => $this->expected]);
    }

    public function testView(): void
    {
        $response = $this->getJson('/orders/id:' . $this->order->getKey());
        $response->assertUnauthorized();

        $response = $this->actingAs($this->user)->getJson('/orders/id:' . $this->order->getKey());
        $response
            ->assertOk()
            ->assertJsonFragment(['code' => $this->order->code]);
    }

    public function testCantCreateOrderWithoutItems(): void
    {
        $shippingMethod = ShippingMethod::factory()->create();

        $response = $this->postJson('/orders', [
            'email' => 'test@example.com',
            'shipping_method_id' => $shippingMethod->getKey(),
            'delivery_address' => [
                'name' => 'Wojtek Testowy',
                'phone' => '+48123321123',
                'address' => 'Gdańska 89/1',
                'zip' => '12-123',
                'city' => 'Bydgoszcz',
                'country' => 'PL',
            ],
            'products' => [],
        ]);

        $response->assertStatus(422);
    }

    public function testUpdateOrderStatusUnauthorized(): void
    {
        Event::fake([OrderStatusUpdated::class]);

        $status = Status::factory()->create();

        $response = $this->postJson('/orders/id:' . $this->order->getKey() . '/status', [
            'status_id' => $status->getKey(),
        ]);

        $response->assertUnauthorized();
        Event::assertNotDispatched(OrderStatusUpdated::class);
    }

    public function testUpdateOrderStatus(): void
    {
        Event::fake([OrderStatusUpdated::class]);

        $status = Status::factory()->create();

        $response = $this->actingAs($this->user)->postJson('/orders/id:' . $this->order->getKey() . '/status', [
            'status_id' => $status->getKey(),
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('orders', [
            'id' => $this->order->getKey(),
            'status_id' => $status->getKey(),
        ]);
        Event::assertDispatched(OrderStatusUpdated::class);
    }
}
