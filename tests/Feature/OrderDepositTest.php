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
use Illuminate\Support\Carbon;
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
            'required' => true,
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

        /** @var Order $order */
        $order = Order::query()->find($response->getData()->data->id);

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
            'price_initial' => 100,
            'price' => 100,
            'name' => $this->product->name,
        ]);
        $orderProduct->schemas()->create([
            'order_product_id' => $orderProduct->getKey(),
            'name' => 'Size',
            'value' => 'XL',
            'price_initial' => 0,
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
            ->assertNoContent();

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

    /**
     * @dataProvider authProvider
     */
    public function testCreateOrderWithProductThatGotItemsAndSchema($user): void
    {
        $this->$user->givePermissionTo('orders.add');

        $this->item->deposits()->create([
            'quantity' => 6,
            'shipping_time' => 4,
        ]);

        $this->product->items()->attach($this->item->getKey(), ['required_quantity' => 2]);

        $response = $this
            ->actingAs($this->$user)
            ->json('POST', '/orders', $this->request);

        $response->assertCreated();
        $order = Order::find($response->getData()->data->id);

        $this->product->refresh();

        $this->assertEquals(0, $this->product->available);

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
            'quantity' => -6,
            'item_id' => $this->item->getKey(),
            'order_product_id' => $order->products->first()->getKey(),
            'shipping_time' => 4,
        ]);
        $this->assertDatabaseHas('items', [
            'id' => $this->item->getKey(),
            'quantity' => 0,
        ]);

        Event::assertDispatched(OrderCreated::class);
    }

    /**
     * Sample example deposit stock
     * - 1 item in 1 days shipping time
     * - 4 items in 3 days shipping time
     * - unlimited number of items in 10 days shipping time
     *
     * The user wants to order 20 items, so the following deposits are created for the order:
     * - 20 items with a time of 10 days shipping time (from unlimited stock)
     * in this case, the item will still have the information that it is available within 1 day
     * Another user orders the same product only in the amount of 3 pieces, so created
     * the following deposits are included in the order:
     * - 3 items in 3 days
     * We have the product again available for 1 day shipping time
     * The next user only buys 1 product, so the following deposits are created for the order:
     * - 1 item in 1 day
     * now this product is available within 3 days shipping time
     *
     * @dataProvider authProvider
     */
    public function testCreateOrdersAndCheckDeposits($user): void
    {
        $this->$user->givePermissionTo('orders.add');
        $this->$user->givePermissionTo('cart.verify');

        $this->item->deposits()->create([
            'quantity' => 1,
            'shipping_time' => 1,
        ]);
        $this->item->deposits()->create([
            'quantity' => 4,
            'shipping_time' => 3,
        ]);
        $this->item->update([
            'unlimited_stock_shipping_time' => 10,
        ]);

        $product = Product::factory()->create([
            'price' => 100,
            'public' => true,
        ]);

        $product->items()->attach($this->item->getKey(), ['required_quantity' => 1]);

        $this->assertDatabaseHas('products', [
            'id' => $product->getKey(),
            'available' => true,
            'quantity' => 5,
            'shipping_time' => 1, // product got 1 days shipping time
        ]);

        // first order 20 product
        $request = [
            'email' => 'test@example.com',
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'delivery_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $product->getKey(),
                    'quantity' => 20,
                    'schemas' => [],
                ],
            ],
        ];

        $response = $this->actingAs($this->$user)->postJson('/cart/process', [
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $product->getKey(),
                    'quantity' => 20,
                    'schemas' => [],
                ],
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonFragment([
                'shipping_time' => 10,
            ]);

        $response = $this
            ->actingAs($this->$user)
            ->json('POST', '/orders', $request);

        $response->assertCreated();

        /** @var Order $order */
        $order = Order::query()->find($response->getData()->data->id); // order created

        $this->assertDatabaseHas('deposits', [ // was taken from the deposit of 20 items in shipping time 10
            'quantity' => -20,
            'item_id' => $this->item->getKey(),
            'order_product_id' => $order->products->first()->getKey(),
            'shipping_time' => 10,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->getKey(),
            'available' => true,
            'quantity' => 5,
            'shipping_time' => 1, // product now got 1 days shipping time
        ]);

        // second order 3 product
        $request = [
            'email' => 'test1@example.com',
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'delivery_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $product->getKey(),
                    'quantity' => 3,
                    'schemas' => [],
                ],
            ],
        ];

        $response = $this->actingAs($this->$user)->postJson('/cart/process', [
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $product->getKey(),
                    'quantity' => 3,
                    'schemas' => [],
                ],
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonFragment([
                'shipping_time' => 3,
            ]);

        $response = $this
            ->actingAs($this->$user)
            ->json('POST', '/orders', $request);

        $response->assertCreated();

        /** @var Order $order */
        $order = Order::query()->find($response->getData()->data->id); //order created

        $this->assertDatabaseHas('deposits', [ //was taken from the deposit of 3 items in shipping time 3
            'quantity' => -3,
            'item_id' => $this->item->getKey(),
            'order_product_id' => $order->products->first()->getKey(),
            'shipping_time' => 3,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->getKey(),
            'shipping_time' => 1, //product now got 1 days shipping time
        ]);

        //third order 1 product
        $request = [
            'email' => 'test1@example.com',
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'delivery_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $product->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
            ],
        ];

        $response = $this->actingAs($this->$user)->postJson('/cart/process', [
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $product->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonFragment([
                'shipping_time' => 1,
            ]);

        $response = $this
            ->actingAs($this->$user)
            ->json('POST', '/orders', $request);

        $response->assertCreated();

        /** @var Order $order */
        $order = Order::query()->find($response->getData()->data->id); //order created

        $this->assertDatabaseHas('deposits', [ //was taken from the deposit of 1 items in shipping time 1
            'quantity' => -1,
            'item_id' => $this->item->getKey(),
            'order_product_id' => $order->products->first()->getKey(),
            'shipping_time' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->getKey(),
            'shipping_time' => 3, //product now got 3 days shipping time
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteOrderCheckDeposits($user): void
    {
        $this->$user->givePermissionTo('orders.edit.status');

        $date = Carbon::now()->addDays(4)->toDateTimeString();

        $this->item->deposits()->create([
            'quantity' => 6,
            'shipping_date' => $date,
        ]);

        $this->product->items()->attach($this->item->getKey(), ['required_quantity' => 2]);

        /** @var Order $order */
        $order = Order::factory()->create();
        $orderProduct = $order->products()->create([
            'product_id' => $this->product->getKey(),
            'quantity' => 2,
            'price_initial' => 100,
            'price' => 100,
            'name' => $this->product->name,
        ]);
        $orderProduct->schemas()->create([
            'order_product_id' => $orderProduct->getKey(),
            'name' => 'Size',
            'value' => 'XL',
            'price_initial' => 0,
            'price' => 0,
        ]);
        $deposit = $orderProduct->deposits()->create([
            'order_product_id' => $orderProduct->getKey(),
            'item_id' => $this->item->getKey(),
            'quantity' => -6,
            'shipping_date' => $date,
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
            ->assertNoContent();

        $this->assertDatabaseHas('orders', [
            'id' => $order->getKey(),
            'status_id' => $status->getKey(),
        ]);
        $this->assertDatabaseMissing('deposits', [
            'id' => $deposit->getKey(),
            'quantity' => -6,
            'shipping_date' => $date,
        ]);
        $this->assertDatabaseHas('items', [
            'id' => $this->item->getKey(),
            'quantity' => 6,
            'shipping_date' => $date,
        ]);

        $this->item->refresh();
        $this->assertEquals(6, $this->item->quantity);

        Event::assertDispatched(OrderUpdatedStatus::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateOrdersWithPreOrderItemAndCheckDeposits($user): void
    {
        $this->$user->givePermissionTo('orders.add');
        $this->$user->givePermissionTo('cart.verify');

        $date = Carbon::now()->addDays(10)->toIso8601String();

        $this->item->update([
            'unlimited_stock_shipping_date' => $date,
        ]);

        $product = Product::factory()->create([
            'price' => 100,
            'public' => true,
        ]);

        $product->items()->attach($this->item->getKey(), ['required_quantity' => 1]);

        $this->assertDatabaseHas('products', [
            'id' => $product->getKey(),
            'shipping_date' => $date,
        ]);

        $request = [
            'email' => 'test@example.com',
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'delivery_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $product->getKey(),
                    'quantity' => 3,
                    'schemas' => [],
                ],
            ],
        ];

        $response = $this->actingAs($this->$user)->postJson('/cart/process', [
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $product->getKey(),
                    'quantity' => 3,
                    'schemas' => [],
                ],
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonFragment([
                'shipping_date' => $date,
            ]);

        $response = $this
            ->actingAs($this->$user)
            ->json('POST', '/orders', $request);

        $response->assertCreated();
        $order = Order::find($response->getData()->data->id);

        $this->assertDatabaseHas('deposits', [
            'quantity' => -3,
            'item_id' => $this->item->getKey(),
            'order_product_id' => $order->products->first()->getKey(),
            'shipping_date' => $date,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->getKey(),
            'shipping_date' => $date,
        ]);
    }
}
