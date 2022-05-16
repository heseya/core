<?php

namespace Tests\Feature;

use App\Enums\ConditionType;
use App\Enums\DiscountTargetType;
use App\Enums\DiscountType;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\IssuerType;
use App\Enums\RoleType;
use App\Enums\SchemaType;
use App\Events\ItemUpdatedQuantity;
use App\Events\OrderCreated;
use App\Listeners\WebHookEventListener;
use App\Models\Address;
use App\Models\ConditionGroup;
use App\Models\Deposit;
use App\Models\Discount;
use App\Models\Item;
use App\Models\Option;
use App\Models\Order;
use App\Models\PriceRange;
use App\Models\Product;
use App\Models\ProductSet;
use App\Models\Role;
use App\Models\Schema;
use App\Models\ShippingMethod;
use App\Models\Status;
use App\Models\WebHook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Spatie\WebhookServer\CallWebhookJob;
use Tests\TestCase;

class OrderCreateTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private ShippingMethod $shippingMethod;
    private ProductSet $category;
    private ProductSet $brand;
    private Address $address;
    private Product $product;
    private string $email;

    public function setUp(): void
    {
        parent::setUp();

        $this->email = $this->faker->freeEmail;

        $this->shippingMethod = ShippingMethod::factory()->create(['public' => true]);
        $lowRange = PriceRange::create(['start' => 0]);
        $lowRange->prices()->create(['value' => 8.11]);

        $highRange = PriceRange::create(['start' => 210]);
        $highRange->prices()->create(['value' => 0.0]);

        $this->shippingMethod->priceRanges()->saveMany([$lowRange, $highRange]);

        $this->address = Address::factory()->make();

        $this->product = Product::factory()->create([
            'public' => true,
        ]);
    }

    public function testCreateOrderUnauthorized(): void
    {
        Event::fake([OrderCreated::class]);

        $response = $this->postJson('/orders', [
            'email' => $this->email,
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'delivery_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 2,
                    'schemas' => [],
                ],
            ],
        ]);

        $response->assertForbidden();
        Event::assertNotDispatched(OrderCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateSimpleOrder($user): void
    {
        $this->$user->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $this->product->update([
            'price' => 10,
        ]);

        $productQuantity = 20;

        $response = $this->actingAs($this->$user)->postJson('/orders', [
            'email' => $this->email,
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'delivery_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => $productQuantity,
                ],
            ],
        ]);

        $response->assertCreated();
        $order = $response->getData()->data;

        $shippingPrice = $this->shippingMethod->getPrice(
            $this->product->price * $productQuantity,
        );
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'email' => $this->email,
            'shipping_price' => $shippingPrice,
            'summary' => $this->product->price * $productQuantity + $shippingPrice,
        ]);
        $this->assertDatabaseHas('addresses', $this->address->toArray());
        $this->assertDatabaseHas('order_products', [
            'order_id' => $order->id,
            'product_id' => $this->product->getKey(),
            'quantity' => 20,
        ]);

        Event::assertDispatched(OrderCreated::class);

        Queue::fake();

        $order = Order::find($order->id);
        $event = new OrderCreated($order);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Queue::assertNotPushed(CallWebhookJob::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateSimpleOrderWithMetadata($user): void
    {
        $this->$user->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $this->product->update([
            'price' => 10,
        ]);

        $productQuantity = 20;

        $this
            ->actingAs($this->$user)
            ->postJson('/orders', [
                'email' => $this->email,
                'shipping_method_id' => $this->shippingMethod->getKey(),
                'delivery_address' => $this->address->toArray(),
                'items' => [
                    [
                        'product_id' => $this->product->getKey(),
                        'quantity' => $productQuantity,
                    ],
                ],
                'metadata' => [
                    'attributeMeta' => 'attributeValue',
                ],
            ])
            ->assertCreated()
            ->assertJsonFragment([
                'metadata' => [
                    'attributeMeta' => 'attributeValue',
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateSimpleOrderWithMetadataPrivate($user): void
    {
        $this->$user->givePermissionTo(['orders.add', 'orders.show_metadata_private']);

        Event::fake([OrderCreated::class]);

        $this->product->update([
            'price' => 10,
        ]);

        $productQuantity = 20;

        $this
            ->actingAs($this->$user)
            ->postJson('/orders', [
                'email' => $this->email,
                'shipping_method_id' => $this->shippingMethod->getKey(),
                'delivery_address' => $this->address->toArray(),
                'items' => [
                    [
                        'product_id' => $this->product->getKey(),
                        'quantity' => $productQuantity,
                    ],
                ],
                'metadata_private' => [
                    'attributeMetaPriv' => 'attributeValue',
                ],
            ])
            ->assertCreated()
            ->assertJsonFragment([
                'metadata_private' => [
                    'attributeMetaPriv' => 'attributeValue',
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateSimpleOrderPaid($user): void
    {
        $this->$user->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $this->product->update([
            'price' => 0,
        ]);

        $productQuantity = 1;

        $freeShipping = ShippingMethod::factory()->create(['public' => true]);
        $lowRange = PriceRange::create(['start' => 0]);
        $lowRange->prices()->create(['value' => 0]);

        $freeShipping->priceRanges()->save($lowRange);

        $response = $this->actingAs($this->$user)->postJson('/orders', [
            'email' => $this->email,
            'shipping_method_id' => $freeShipping->getKey(),
            'delivery_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => $productQuantity,
                ],
            ],
        ]);

        $response->assertCreated();
        $order = $response->getData()->data;

        $response->assertJsonFragment([
            'id' => $order->id,
            'summary' => 0,
            'paid' => true,
            'payable' => false,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'email' => $this->email,
            'shipping_price' => 0,
            'summary' => 0,
            'paid' => true,
        ]);

        Event::assertDispatched(OrderCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateSimpleOrderWithWebHookQueue($user): void
    {
        $this->$user->givePermissionTo('orders.add');

        WebHook::factory()->create([
            'events' => [
                'OrderCreated',
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Event::fake([OrderCreated::class]);

        $this->product->update([
            'price' => 10,
        ]);

        $productQuantity = 20;

        $response = $this->actingAs($this->$user)->postJson('/orders', [
            'email' => $this->email,
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'delivery_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => $productQuantity,
                ],
            ],
        ]);

        $response->assertCreated();
        $order = $response->getData()->data;

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'email' => $this->email,
            'shipping_price' => $this->shippingMethod->getPrice(
                $this->product->price * $productQuantity,
            ),
        ]);
        $this->assertDatabaseHas('addresses', $this->address->toArray());
        $this->assertDatabaseHas('order_products', [
            'order_id' => $order->id,
            'product_id' => $this->product->getKey(),
            'quantity' => 20,
        ]);

        Event::assertDispatched(OrderCreated::class);

        Queue::fake();

        $order = Order::find($order->id);
        $event = new OrderCreated($order);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Queue::assertPushed(CallWebhookJob::class);
    }

    public function testCreateSimpleOrderWithWebHookEvent(): array
    {
        $this->user->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $response = $this->actingAs($this->user)->postJson('/orders', [
            'email' => $this->email,
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'delivery_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 20,
                ],
            ],
        ]);

        $order = Order::find($response->getData()->data->id)->with('shippingMethod')->first();

        Event::assertDispatched(OrderCreated::class);
        return [$order, new OrderCreated($order)];
    }

    public function testCreateSimpleOrderUnauthenticatedWithWebHookEvent(): array
    {
        $role = Role::where('type', RoleType::UNAUTHENTICATED)->firstOrFail();
        $role->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $response = $this->postJson('/orders', [
            'email' => $this->email,
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'delivery_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 20,
                ],
            ],
        ]);

        $order = Order::find($response->getData()->data->id)->with('shippingMethod')->first();

        Event::assertDispatched(OrderCreated::class);
        return [$order, new OrderCreated($order)];
    }

    /**
     * @dataProvider authProvider
     *
     * @depends testCreateSimpleOrderWithWebHookEvent
     * @depends testCreateSimpleOrderUnauthenticatedWithWebHookEvent
     */
    public function testOrderCreatedWebHookDispatch($user, $payload, $payload2): void
    {
        $webHook = WebHook::factory()->create([
            'events' => [
                'OrderCreated',
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Bus::fake();

        [$order, $event] = $payload;
        [$orderUnauthenticated, $eventUnauthenticated] = $payload2;
        $listener = new WebHookEventListener();

        $listener->handle($event);

        // Zalogowany użytkownik
        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $order) {
            $payload = $job->payload;
            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $order->getKey()
                && $payload['data_type'] === 'Order'
                && $payload['event'] === 'OrderCreated';
        });

        $listener->handle($eventUnauthenticated);

        // Niezalogowany użytkownik
        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $orderUnauthenticated) {
            $payload = $job->payload;
            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $orderUnauthenticated->getKey()
                && $payload['data_type'] === 'Order'
                && $payload['event'] === 'OrderCreated'
                && $payload['issuer_type'] === IssuerType::UNAUTHENTICATED;
        });
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateOrder($user): void
    {
        $this->$user->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $schema = Schema::factory()->create([
            'type' => 'string',
            'price' => 10,
            'hidden' => false,
        ]);

        $this->product->schemas()->sync([$schema->getKey()]);
        $this->product->update([
            'price' => 100,
        ]);

        $productQuantity = 2;

        $response = $this->actingAs($this->$user)->postJson('/orders', [
            'email' => $this->email,
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'delivery_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => $productQuantity,
                    'schemas' => [
                        $schema->getKey() => 'Test',
                    ],
                ],
            ],
        ]);

        $response->assertCreated();
        $order = Order::find($response->getData()->data->id);

        $schemaPrice = $schema->getPrice('Test', [
            $schema->getKey() => 'Test',
        ]);

        $shippingPrice = $this->shippingMethod->getPrice(
            ($this->product->price + $schemaPrice) * $productQuantity,
        );

        $this->assertDatabaseHas('orders', [
            'id' => $order->getKey(),
            'email' => $this->email,
            'shipping_price' => $this->shippingMethod->getPrice(
                ($this->product->price + $schemaPrice) * $productQuantity,
            ),
            'summary' => ($this->product->price + $schemaPrice) * $productQuantity + $shippingPrice,
        ]);
        $this->assertDatabaseHas('addresses', $this->address->toArray());
        $this->assertDatabaseHas('order_products', [
            'order_id' => $order->getKey(),
            'product_id' => $this->product->getKey(),
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('order_schemas', [
            'order_product_id' => $order->products[0]->getKey(),
            'name' => $schema->name,
            'value' => 'Test',
        ]);

        Event::assertDispatched(OrderCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateOrderNullInvoiceAddress($user): void
    {
        $this->$user->givePermissionTo('orders.add');

        $schema = Schema::factory()->create([
            'type' => 'string',
            'price' => 10,
            'hidden' => false,
        ]);

        $this->product->schemas()->sync([$schema->getKey()]);
        $this->product->update([
            'price' => 100,
        ]);

        $productQuantity = 2;

        $response = $this
            ->actingAs($this->$user)
            ->postJson('/orders', [
                'email' => $this->email,
                'shipping_method_id' => $this->shippingMethod->getKey(),
                'delivery_address' => $this->address->toArray(),
                'invoice_address' => null,
                'items' => [
                    [
                        'product_id' => $this->product->getKey(),
                        'quantity' => $productQuantity,
                        'schemas' => [
                            $schema->getKey() => 'Test',
                        ],
                    ],
                ],
            ]);

        $response->assertCreated();
        $order = Order::find($response->getData()->data->id);

        $this->assertEmpty($order->invoiceAddress);
    }
    /**
     * @dataProvider authProvider
     */
    public function testCreateOrderInvoiceAddress($user): void
    {
        $this->$user->givePermissionTo('orders.add');

        $schema = Schema::factory()->create([
            'type' => 'string',
            'price' => 10,
            'hidden' => false,
        ]);

        $this->product->schemas()->sync([$schema->getKey()]);
        $this->product->update([
            'price' => 100,
        ]);

        $productQuantity = 2;

        $invoiceAddress = Address::factory()->make();

        $response = $this
            ->actingAs($this->$user)
            ->postJson('/orders', [
                'email' => $this->email,
                'shipping_method_id' => $this->shippingMethod->getKey(),
                'delivery_address' => $this->address->toArray(),
                'invoice_address' => $invoiceAddress->toArray(),
                'items' => [
                    [
                        'product_id' => $this->product->getKey(),
                        'quantity' => $productQuantity,
                        'schemas' => [
                            $schema->getKey() => 'Test',
                        ],
                    ],
                ],
            ]);

        $response->assertCreated();
        $order = Order::find($response->getData()->data->id);

        $this->assertEquals(
            $invoiceAddress->toArray(),
            $order->invoiceAddress->only('name', 'phone', 'address', 'zip', 'city', 'country')
        );
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateOrderWithWebHook($user): void
    {
        $this->$user->givePermissionTo('orders.add');

        $webHook = WebHook::factory()->create([
            'events' => [
                'ItemUpdatedQuantity',
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Event::fake([OrderCreated::class, ItemUpdatedQuantity::class]);

        $item = Item::factory()->create();

        Deposit::factory()->create([
            'item_id' => $item->getKey(),
            'quantity' => 100,
        ]);

        $schema = Schema::factory()->create([
            'type' => 'select',
            'price' => 10,
            'hidden' => false,
        ]);

        $option = Option::factory()->create([
            'name' => 'A',
            'price' => 10,
            'disabled' => false,
            'order' => 0,
            'schema_id' => $schema->getKey(),
        ]);

        $option->items()->sync([
            $item->getKey(),
        ]);

        $this->product->schemas()->sync([$schema->getKey()]);
        $this->product->update([
            'price' => 100,
        ]);

        $productQuantity = 2;

        $response = $this->actingAs($this->$user)->postJson('/orders', [
            'email' => $this->email,
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'delivery_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => $productQuantity,
                    'schemas' => [
                        $schema->getKey() => $option->getKey(),
                    ],
                ],
            ],
        ]);

        $response->assertCreated();
        $order = Order::find($response->getData()->data->id);

        $schemaPrice = $schema->getPrice($option->getKey(), [
            $schema->getKey() => $option->getKey(),
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'email' => $this->email,
            'shipping_price' => $this->shippingMethod->getPrice(
                ($this->product->price + $schemaPrice) * $productQuantity,
            ),
        ]);
        $this->assertDatabaseHas('addresses', $this->address->toArray());
        $this->assertDatabaseHas('order_products', [
            'order_id' => $order->getKey(),
            'product_id' => $this->product->getKey(),
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('order_schemas', [
            'order_product_id' => $order->products[0]->getKey(),
            'name' => $schema->name,
            'value' => $option->name,
        ]);

        Event::assertDispatched(OrderCreated::class);
        Event::assertDispatched(ItemUpdatedQuantity::class);

        Bus::fake();

        $item = Item::find($item->getKey());
        $event = new ItemUpdatedQuantity($item);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $item) {
            $payload = $job->payload;
            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $item->getKey()
                && $payload['data_type'] === 'Item'
                && $payload['event'] === 'ItemUpdatedQuantity';
        });
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateOrderHiddenSchema($user): void
    {
        $this->$user->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $schema = Schema::factory()->create([
            'type' => 'string',
            'price' => 10,
            'hidden' => true,
        ]);

        $this->product->schemas()->sync([$schema->getKey()]);
        $this->product->update([
            'price' => 100,
        ]);

        $productQuantity = 2;

        $response = $this->actingAs($this->$user)->postJson('/orders', [
            'email' => $this->email,
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'delivery_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => $productQuantity,
                    'schemas' => [
                        $schema->getKey() => 'Test',
                    ],
                ],
            ],
        ]);

        $response->assertCreated();
        $order = Order::find($response->getData()->data->id);

        $schemaPrice = $schema->getPrice('Test', [
            $schema->getKey() => 'Test',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'email' => $this->email,
            'shipping_price' => $this->shippingMethod->getPrice(
                ($this->product->price + $schemaPrice) * $productQuantity,
            ),
        ]);
        $this->assertDatabaseHas('addresses', $this->address->toArray());
        $this->assertDatabaseHas('order_products', [
            'order_id' => $order->getKey(),
            'product_id' => $this->product->getKey(),
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('order_schemas', [
            'order_product_id' => $order->products[0]->getKey(),
            'name' => $schema->name,
            'value' => 'Test',
        ]);

        Event::assertDispatched(OrderCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateOrderNonRequiredSchemaEmpty($user): void
    {
        $this->$user->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $schemaPrice = 10;
        $schema = Schema::factory()->create([
            'type' => SchemaType::getKey(SchemaType::STRING),
            'price' => $schemaPrice,
            'required' => false, // Important!
        ]);

        $productPrice = 100;
        $this->product->schemas()->sync([$schema->getKey()]);
        $this->product->update([
            'price' => $productPrice,
        ]);

        $response = $this->actingAs($this->$user)->postJson('/orders', [
            'email' => 'test@example.com',
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'delivery_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                    'schemas' => [
                        $schema->getKey() => '',
                    ],
                ],
            ],
        ]);

        $response->assertCreated();

        /** @var Order $order */
        $order = Order::findOrFail(
            $response->json('data.id'),
        );

        // Expected price doesn't include empty schema
        $expectedOrderPrice = $productPrice + $this->shippingMethod->getPrice($productPrice);
        $this->assertEquals($expectedOrderPrice, $order->summary);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateOrderNoSalesIds($user): void
    {
        $this->$user->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $discount = Discount::factory()->create([
            'description' => 'Testowy kupon',
            'code' => null,
            'value' => 10,
            'type' => DiscountType::PERCENTAGE,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ]);
        $shippingMethod = ShippingMethod::factory()->create();
        $discount->products()->attach($this->product->getKey());

        $conditionGroup = ConditionGroup::create();

        $conditionGroup->conditions()->create([
            'type' => ConditionType::DATE_BETWEEN,
            'value' => [
                'end_at' => Carbon::yesterday(),
                'is_in_range' => true,
            ],
        ]);

        $discount->conditionGroups()->attach($conditionGroup);

        $response = $this->actingAs($this->$user)->json('POST', '/orders', [
            'email' => $this->email,
            'shipping_method_id' => $shippingMethod->getKey(),
            'delivery_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertCreated();

        Event::assertDispatched(OrderCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateOrderWithDiscount($user): void
    {
        $this->$user->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $discount = Discount::factory()->create([
            'description' => 'Testowy kupon',
            'code' => 'S43SA2',
            'value' => 10,
            'type' => DiscountType::PERCENTAGE,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ]);
        $shippingMethod = ShippingMethod::factory()->create();
        $discount->products()->attach($this->product->getKey());

        $response = $this->actingAs($this->$user)->json('POST', '/orders', [
            'email' => $this->email,
            'shipping_method_id' => $shippingMethod->getKey(),
            'delivery_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                ],
            ],
            'coupons' => [
                $discount->code,
            ],
        ]);

        $response->assertCreated();
        $order = Order::find($response->getData()->data->id);

        $this->assertDatabaseHas('orders', [
            'id' => $order->getKey(),
            'email' => $this->email,
            'shipping_price' => $shippingMethod->getPrice(
                $this->product->price * 1,
            ),
        ]);

        Event::assertDispatched(OrderCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateOrderWithDiscountMinimalPrices($user): void
    {
        $this->$user->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $product = Product::factory()->create([
            'public' => true,
            'price' => 150,
        ]);

        $discount = Discount::factory()->create([
            'description' => 'Testowy kupon',
            'code' => 'S43SA2',
            'value' => 95,
            'type' => DiscountType::PERCENTAGE,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ]);

        $saleOrder = Discount::factory()->create([
            'description' => 'Testowy kupon',
            'name' => 'Kupon order',
            'value' => 50,
            'type' => DiscountType::AMOUNT,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
            'code' => null,
        ]);

        $couponShipping = Discount::factory()->create([
            'description' => 'Testowy kupon',
            'name' => 'Kupon shipping',
            'value' => 15,
            'type' => DiscountType::AMOUNT,
            'target_type' => DiscountTargetType::SHIPPING_PRICE,
            'target_is_allow_list' => false,
        ]);

        $discount->products()->attach($product->getKey());

        $shippingMethod = ShippingMethod::factory()->create(['public' => true]);
        $lowRange = PriceRange::create(['start' => 0]);
        $lowRange->prices()->create(['value' => 10]);

        $shippingMethod->priceRanges()->saveMany([$lowRange]);

        $response = $this->actingAs($this->$user)->json('POST', '/orders', [
            'email' => $this->email,
            'shipping_method_id' => $shippingMethod->getKey(),
            'delivery_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $product->getKey(),
                    'quantity' => 1,
                ],
            ],
            'coupons' => [
                $discount->code,
                $couponShipping->code,
            ],
        ]);

        $response->assertCreated()
            ->assertJsonFragment([
                'cart_total_initial' => 150,
                'cart_total' => 0,
                'shipping_price_initial' => 10,
                'shipping_price' => 0,
                'summary' => 0,
            ]);
        $order = Order::find($response->getData()->data->id);

        $this->assertDatabaseHas('order_products', [
            'order_id' => $order->getKey(),
            'product_id' => $product->getKey(),
            'price_initial' => 150,
            'price' => 7.50,
        ]);

        $this->assertDatabaseHas('order_discounts', [
            'discount_id' => $discount->getKey(),
            'applied_discount' => 142.5, // -95%
        ]);

        $this->assertDatabaseHas('order_discounts', [
            'model_id' => $order->getKey(),
            'discount_id' => $saleOrder->getKey(),
            'applied_discount' => 7.50, // discount -50, but price should be 7.50 when discount is applied
        ]);

        $this->assertDatabaseHas('order_discounts', [
            'model_id' => $order->getKey(),
            'discount_id' => $couponShipping->getKey(),
            'applied_discount' => 10, // discount -15, but shipping_price_initial is 10
        ]);

        Event::assertDispatched(OrderCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCantCreateOrderWithoutItems($user): void
    {
        $this->$user->givePermissionTo('orders.add');

        $shippingMethod = ShippingMethod::factory()->create();

        $response = $this->actingAs($this->$user)->json('POST', '/orders', [
            'email' => $this->email,
            'shipping_method_id' => $shippingMethod->getKey(),
            'delivery_address' => [
                'name' => 'Wojtek Testowy',
                'phone' => '+48123321123',
                'address' => 'Gdańska 89/1',
                'zip' => '12-123',
                'city' => 'Bydgoszcz',
                'country' => 'PL',
            ],
            'items' => [],
        ]);

        $response->assertStatus(422);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCantCreateOrderWithExpiredDiscount($user): void
    {
        $this->$user->givePermissionTo('orders.add');

        $discount = Discount::factory()->create([
            'description' => 'Testowy kupon',
            'code' => 'S43SA2',
            'value' => 10,
            'type' => DiscountType::PERCENTAGE,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
        ]);

        $conditionGroup = ConditionGroup::create();

        $conditionGroup->conditions()->create([
            'type' => ConditionType::DATE_BETWEEN,
            'value' => [
                'end_at' => Carbon::yesterday(),
                'is_in_range' => true,
            ],
        ]);

        $discount->conditionGroups()->attach($conditionGroup);

        $shippingMethod = ShippingMethod::factory()->create();

        $response = $this->actingAs($this->$user)->json('POST', '/orders', [
            'email' => $this->email,
            'shipping_method_id' => $shippingMethod->getKey(),
            'delivery_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                ],
            ],
            'coupons' => [
                $discount->code,
            ],
        ]);

        $response->assertStatus(422);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCantCreateOrderWithDiscountBeforeStart($user): void
    {
        $this->$user->givePermissionTo('orders.add');

        $discount = Discount::factory()->create([
            'description' => 'Testowy kupon',
            'code' => 'S43SA2',
            'value' => 10,
            'type' => DiscountType::PERCENTAGE,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
        ]);

        $conditionGroup = ConditionGroup::create();

        $conditionGroup->conditions()->create([
            'type' => ConditionType::DATE_BETWEEN,
            'value' => [
                'start_at' => Carbon::tomorrow(),
                'is_in_range' => true,
            ],
        ]);

        $discount->conditionGroups()->attach($conditionGroup);

        $shippingMethod = ShippingMethod::factory()->create();

        $response = $this->actingAs($this->$user)->json('POST', '/orders', [
            'email' => $this->email,
            'shipping_method_id' => $shippingMethod->getKey(),
            'delivery_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                ],
            ],
            'coupons' => [
                $discount->code,
            ],
        ]);

        $response->assertStatus(422);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateOrderWithEnabledAudit($user): void
    {
        Config::set('audit.console', true);
        $this->$user->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $productQuantity = 2;

        $response = $this->actingAs($this->$user)->postJson('/orders', [
            'email' => $this->email,
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'delivery_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => $productQuantity,
                ],
            ],
        ]);

        $response->assertCreated();
        $order = Order::find($response->getData()->data->id);

        $this->assertDatabaseHas('audits', [
            'auditable_id' => $order->getKey(),
            'auditable_type' => Order::class,
            'event' => 'created',
        ]);

        Event::assertDispatched(OrderCreated::class);
    }

    public function testCreateOrderWithoutAnyStatuses(): void
    {
        Status::query()->delete();

        $this->user->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $response = $this->actingAs($this->user)->postJson('/orders', [
            'email' => $this->email,
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'delivery_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                ],
            ],
        ]);

        $response
            ->assertStatus(500)
            ->assertJsonFragment(['message' => Exceptions::SERVER_ORDER_STATUSES_NOT_CONFIGURED]);

        Event::assertNotDispatched(OrderCreated::class);
    }

    public function testCreateOrderWithEmptyVat(): void
    {
        $this->user->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $this
            ->actingAs($this->user)
            ->postJson('/orders', [
                'email' => $this->email,
                'shipping_method_id' => $this->shippingMethod->getKey(),
                'delivery_address' => [
                    'name' => 'Johny Mielony',
                    'address' => 'Street 89',
                    'vat' => '',
                    'zip' => '80-200',
                    'city' => 'City',
                    'country' => 'PL',
                    'phone' => '+48543234123',
                ],
                'items' => [
                    [
                        'product_id' => $this->product->getKey(),
                        'quantity' => 20,
                    ],
                ],
            ])
            ->assertCreated();

        $this->assertDatabaseHas('addresses', [
            'name' => 'Johny Mielony',
            'address' => 'Street 89',
            'zip' => '80-200',
            'vat' => null,
            'city' => 'City',
            'country' => 'PL',
            'phone' => '+48543234123',
        ]);
    }
}
