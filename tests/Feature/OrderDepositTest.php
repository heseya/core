<?php

namespace Tests\Feature;

use App\Events\OrderCreated;
use App\Events\OrderUpdatedStatus;
use App\Models\Address;
use App\Models\Item;
use App\Models\Option;
use App\Models\Order;
use App\Models\Product;
use App\Models\Schema;
use App\Models\Status;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Traits\CreateShippingMethod;

class OrderDepositTest extends TestCase
{
    use CreateShippingMethod;

    private Product $product;
    private Schema $schema;
    private Option $option;
    private Item $item;
    private Address $address;

    private array $request;

    public function setUp(): void
    {
        parent::setUp();

        $this->createShippingMethod();

        Event::fake([OrderCreated::class, OrderUpdatedStatus::class]);

        $this->product = Product::factory()->create([
            'price' => 100,
            'public' => true,
        ]);
        $this->schema = Schema::factory()->create([
            'type' => 'select',
            'price' => 0,
            'hidden' => false,
        ]);
        $this->product->schemas()->sync([$this->schema->getKey()]);
        $this->option = $this->schema->options()->create([
            'name' => 'XL',
            'price' => 0,
        ]);
        $this->item = Item::factory()->create();
        $this->option->items()->sync([$this->item->getKey()]);
        $this->address = Address::factory()->create();

        $this->request = [
            'email' => 'test@example.com',
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'delivery_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 2,
                    'schemas' => [
                        $this->schema->getKey() => $this->option->getKey(),
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider authProvider
     */
    public function testCantCreateOrder($user): void
    {
        $this->$user->givePermissionTo('orders.add');

        $this
            ->actingAs($this->$user)
            ->json('POST', '/orders', $this->request)
            ->assertUnprocessable();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateOrder($user): void
    {
        $this->$user->givePermissionTo('orders.add');

        $this->item->deposits()->create([
            'quantity' => 2,
        ]);

        $response = $this
            ->actingAs($this->$user)
            ->json('POST', '/orders', $this->request);

        $response->assertCreated();
        $order = Order::find($response->getData()->data->id);

        $this->assertDatabaseHas('orders', [
            'id' => $order->getKey(),
            'email' => 'test@example.com',
        ]);
        $this->assertDatabaseHas('addresses', $this->address->toArray());
        $this->assertDatabaseHas('order_products', [
            'order_id' => $order->getKey(),
            'product_id' => $this->product->getKey(),
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('order_schemas', [
            'order_product_id' => $order->products->first()->getKey(),
            'name' => $this->schema->name,
            'value' => 'XL',
        ]);
        $this->assertDatabaseHas('deposits', [
            'quantity' => -2,
            'item_id' => $this->item->getKey(),
            'order_product_id' => $order->products->first()->getKey(),
        ]);
        $this->assertDatabaseHas('items', [
            'id' => $this->item->getKey(),
            'quantity' => 0,
        ]);

        Event::assertDispatched(OrderCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteOrder($user): void
    {
        $this->$user->givePermissionTo('orders.edit.status');

        $this->item->deposits()->create([
            'quantity' => 2,
        ]);

        $order = Order::factory()->create();
        $orderProduct = $order->products()->create([
            'product_id' => $this->product->getKey(),
            'quantity' => 2,
            'price' => 100,
        ]);
        $orderProduct->schemas()->create([
            'order_product_id' => $orderProduct->getKey(),
            'name' => 'Size',
            'value' => 'XL',
            'price' => 0,
        ]);
        $deposit = $orderProduct->deposits()->create([
            'order_product_id' => $orderProduct->getKey(),
            'item_id' => $this->item->getKey(),
            'quantity' => -2,
        ]);

        $status = Status::factory()->create([
            'cancel' => true,
        ]);

        $this->assertEquals(0, $this->item->quantity);

        $this
            ->actingAs($this->$user)
            ->json('PATCH', "/orders/id:{$order->getKey()}/status", [
                'status_id' => $status->getKey(),
            ])
            ->assertOk();

        $this->assertDatabaseHas('orders', [
            'id' => $order->getKey(),
            'status_id' => $status->getKey(),
        ]);
        $this->assertDatabaseMissing('deposits', [
            'id' => $deposit->getKey(),
            'quantity' => -2,
        ]);
        $this->assertDatabaseHas('items', [
            'id' => $this->item->getKey(),
            'quantity' => 2,
        ]);

        $this->item->refresh();
        $this->assertEquals(2, $this->item->quantity);

        Event::assertDispatched(OrderUpdatedStatus::class);
    }

    /**
     * You cannot buy an item whose schematics require more items than there are in stock.
     *
     * @dataProvider authProvider
     */
    public function testCantCreateOrderWithoutItems($user): void
    {
        $this->$user->givePermissionTo('orders.add');

        $this->item->deposits()->create([
            'quantity' => 1,
        ]);

        $this
            ->actingAs($this->$user)
            ->json('POST', '/orders', $this->request)
            ->assertUnprocessable();

        Event::assertNotDispatched(OrderCreated::class);
    }

    /**
     * HES-488
     *
     * If an order has two required schemes using the same item,
     * you cannot buy the product when there is only one item left in stock.
     *
     * @dataProvider authProvider
     */
    public function testCantCreateOrderWithoutMultipleItems($user): void
    {
        $schema = Schema::factory()->create([
            'type' => 'select',
            'price' => 0,
            'hidden' => false,
        ]);
        $this->product->schemas()->sync([$schema->getKey()]);
        $option = $schema->options()->create([
            'name' => 'XL',
            'price' => 0,
        ]);
        $option->items()->sync([$this->item->getKey()]);

        $this->item->deposits()->create([
            'quantity' => 1,
        ]);

        $this->$user->givePermissionTo('orders.add');

        $this->request['items'] = [
            'product_id' => $this->product->getKey(),
            'quantity' => 1,
            'schemas' => [
                $this->schema->getKey() => $this->option->getKey(),
                $schema->getKey() => $option->getKey(),
            ],
        ];

        $this
            ->actingAs($this->$user)
            ->json('POST', '/orders', $this->request)
            ->assertUnprocessable();

        Event::assertNotDispatched(OrderCreated::class);
    }
}
