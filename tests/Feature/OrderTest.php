<?php

namespace Tests\Feature;

use App\Events\OrderStatusUpdated;
use App\Models\Order;
use App\Models\Payment;
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

    public function testOverpaid(): void
    {
        $this->order->payments()->save(Payment::factory()->make([
            'amount' => $this->order->summary * 2,
            'payed' => true,
        ]));

        $this->assertTrue(
            Order::findOrFail($this->order->getKey())->isPayed(),
        );
    }

    public function testIndexUnauthorized(): void
    {
        $response = $this->getJson('/orders');
        $response->assertForbidden();
    }

    public function testIndex(): void
    {
        $this->user->givePermissionTo('orders.show');

        $this
            ->actingAs($this->user)
            ->getJson('/orders')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure(['data' => [
                0 => $this->expected_structure,
            ]])
            ->assertJson(['data' => [
                0 => $this->expected,
            ]]);

        $this->assertQueryCountLessThan(15);
    }

    public function testIndexPerformance(): void
    {
        $this->user->givePermissionTo('orders.show');

        Order::factory()->count(499)->create();

        $this
            ->actingAs($this->user)
            ->getJson('/orders?limit=500')
            ->assertOk()
            ->assertJsonCount(500, 'data');

        $this->assertQueryCountLessThan(15);
    }

    public function testViewUnauthorized(): void
    {
        $response = $this->getJson('/orders/id:' . $this->order->getKey());
        $response->assertForbidden();
    }

    public function testView(): void
    {
        $this->user->givePermissionTo('orders.show_details');

        $response = $this->actingAs($this->user)
            ->getJson('/orders/id:' . $this->order->getKey());
        $response
            ->assertOk()
            ->assertJsonFragment(['code' => $this->order->code]);
    }

    public function testViewSummaryUnauthorized(): void
    {
        $response = $this->getJson('/orders/' . $this->order->code);
        $response->assertForbidden();
    }

    public function testViewSummary(): void
    {
        $this->user->givePermissionTo('orders.show_summary');

        $response = $this->actingAs($this->user)
            ->getJson('/orders/' . $this->order->code);
        $response
            ->assertOk()
            ->assertJsonStructure(['data' => $this->expected_structure])
            ->assertJson(['data' => $this->expected]);
    }

    public function testViewOverpaid(): void
    {
        $this->user->givePermissionTo('orders.show_details');

        $summaryPaid = $this->order->summary * 2;

        $this->order->payments()->save(Payment::factory()->make([
            'amount' => $summaryPaid,
            'payed' => true,
        ]));

        $response = $this->actingAs($this->user)
            ->getJson('/orders/id:' . $this->order->getKey());
        $response
            ->assertOk()
            ->assertJsonFragment([
                'payed' => true,
                'summary_paid' => $summaryPaid
            ]);
    }

    public function testViewOverpaidSummary(): void
    {
        $this->user->givePermissionTo('orders.show_summary');

        $this->order->payments()->save(Payment::factory()->make([
            'amount' => $this->order->summary * 2,
            'payed' => true,
        ]));

        $response = $this->actingAs($this->user)
            ->getJson('/orders/' . $this->order->code);
        $response
            ->assertOk()
            ->assertJsonFragment(['payed' => true]);
    }

    public function testUpdateOrderStatusUnauthorized(): void
    {
        Event::fake([OrderStatusUpdated::class]);

        $status = Status::factory()->create();

        $response = $this->postJson('/orders/id:' . $this->order->getKey() . '/status', [
            'status_id' => $status->getKey(),
        ]);

        $response->assertForbidden();
        Event::assertNotDispatched(OrderStatusUpdated::class);
    }

    public function testUpdateOrderStatus(): void
    {
        $this->user->givePermissionTo('orders.edit.status');

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

    public function testViewUnderpaid(): void
    {
        $this->user->givePermissionTo('orders.show_details');

        $summaryPaid = $this->order->summary / 2;

        $this->order->payments()->save(Payment::factory()->make([
            'amount' => $summaryPaid,
            'payed' => true,
        ]));

        $response = $this->actingAs($this->user)
            ->getJson('/orders/id:' . $this->order->getKey());
        $response
            ->assertOk()
            ->assertJsonFragment([
                'payed' => false,
                'summary_paid' => $summaryPaid,
            ]);
    }

    public function testViewUnderpaidSummary(): void
    {
        $this->user->givePermissionTo('orders.show_summary');

        $this->order->payments()->save(Payment::factory()->make([
            'amount' => $this->order->summary / 2,
            'payed' => true,
        ]));

        $response = $this->actingAs($this->user)
            ->getJson('/orders/' . $this->order->code);
        $response
            ->assertOk()
            ->assertJsonFragment(['payed' => false]);
    }
}
