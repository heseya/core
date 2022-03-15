<?php

namespace Tests\Feature;

use App\Enums\DiscountType;
use App\Enums\IssuerType;
use App\Enums\RoleType;
use App\Enums\SchemaType;
use App\Events\ItemUpdatedQuantity;
use App\Events\OrderCreated;
use App\Listeners\WebHookEventListener;
use App\Models\Address;
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
use App\Models\WebHook;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Bus;
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
            'billing_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 2,
                    'schemas' => []
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
            'billing_address' => $this->address->toArray(),
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
            'billing_address' => $this->address->toArray(),
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
                'OrderCreated'
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
            'billing_address' => $this->address->toArray(),
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
            'billing_address' => $this->address->toArray(),
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
            'billing_address' => $this->address->toArray(),
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
     * @depends testCreateSimpleOrderWithWebHookEvent
     * @depends testCreateSimpleOrderUnauthenticatedWithWebHookEvent
     */
    public function testOrderCreatedWebHookDispatch($user, $payload, $payload2): void
    {
        $webHook = WebHook::factory()->create([
            'events' => [
                'OrderCreated'
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
            'billing_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => $productQuantity,
                    'schemas' => [
                        $schema->getKey() => 'Test',
                    ]
                ],
            ],
        ]);

        $response->assertCreated();
        $order = Order::find($response->getData()->data->id);

        $schemaPrice = $schema->getPrice('Test', [
            $schema->getKey() => 'Test',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->getKey(),
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
    public function testCreateOrderWithWebHook($user): void
    {
        $this->$user->givePermissionTo('orders.add');

        $webHook = WebHook::factory()->create([
            'events' => [
                'ItemUpdatedQuantity'
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Event::fake([OrderCreated::class, ItemUpdatedQuantity::class]);

        $item = Item::factory()->create();

        $deposit = Deposit::factory()->create([
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
            'billing_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => $productQuantity,
                    'schemas' => [
                        $schema->getKey() => $option->getKey(),
                    ]
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
            'billing_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => $productQuantity,
                    'schemas' => [
                        $schema->getKey() => 'Test',
                    ]
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
            'billing_address' => $this->address->toArray(),
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
    public function testCreateOrderWithDiscount($user): void
    {
        $this->$user->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $discount = Discount::factory()->create([
            'description' => 'Testowy kupon',
            'code' => 'S43SA2',
            'discount' => 10,
            'type' => DiscountType::PERCENTAGE,
            'max_uses' => 20,
            'starts_at' => Carbon::yesterday(),
            'expires_at' => Carbon::tomorrow()
        ]);
        $shippingMethod = ShippingMethod::factory()->create();

        $response = $this->actingAs($this->$user)->json('POST', '/orders', [
            'email' => $this->email,
            'shipping_method_id' => $shippingMethod->getKey(),
            'delivery_address' => $this->address->toArray(),
            'billing_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                ],
            ],
            'discounts' => [
                $discount->code
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
    public function testCantCreateOrderWithoutBillingAddress($user): void
    {
        $this->$user->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $this->product->update([
            'price' => 10,
        ]);

        $this->actingAs($this->$user)->postJson('/orders',
            [
                'email' => $this->email,
                'shipping_method_id' => $this->shippingMethod->getKey(),
                'delivery_address' => $this->address->toArray(),
                'items' => [
                    [
                        'product_id' => $this->product->getKey(),
                        'quantity' => 20,
                    ],
                ],
            ])
            ->assertUnprocessable();

        Event::assertNotDispatched(OrderCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCantCreateOrderWithExpiredDiscount($user):void
    {
        $this->$user->givePermissionTo('orders.add');

        $discount = Discount::factory()->create([
            'description' => 'Testowy kupon',
            'code' => 'S43SA2',
            'discount' => 10,
            'type' => DiscountType::PERCENTAGE,
            'max_uses' => 20,
            'starts_at' => Carbon::now()->subDay(),
            'expires_at' => Carbon::now()->subHour()
        ]);
        $shippingMethod = ShippingMethod::factory()->create();

        $response = $this->actingAs($this->$user)->json('POST', '/orders', [
            'email' => $this->email,
            'shipping_method_id' => $shippingMethod->getKey(),
            'delivery_address' => $this->address->toArray(),
            'billing_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                ],
            ],
            'discounts' => [
                $discount->code
            ],
        ]);

        $response->assertStatus(422);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCantCreateOrderWithDiscountBeforeStart($user):void
    {
        $this->$user->givePermissionTo('orders.add');

        $discount = Discount::factory()->create([
            'description' => 'Testowy kupon',
            'code' => 'S43SA2',
            'discount' => 10,
            'type' => DiscountType::PERCENTAGE,
            'max_uses' => 20,
            'starts_at' => Carbon::now()->addDay(),
            'expires_at' => Carbon::now()->addDays(2)
        ]);
        $shippingMethod = ShippingMethod::factory()->create();

        $response = $this->actingAs($this->$user)->json('POST', '/orders', [
            'email' => $this->email,
            'shipping_method_id' => $shippingMethod->getKey(),
            'delivery_address' => $this->address->toArray(),
            'billing_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                ],
            ],
            'discounts' => [
                $discount->code
            ],
        ]);

        $response->assertStatus(422);
    }
}
