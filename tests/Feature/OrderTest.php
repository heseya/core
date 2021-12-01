<?php

namespace Tests\Feature;

use App\Events\OrderCreated;
use App\Events\ItemUpdatedQuantity;
use App\Events\OrderUpdatedStatus;
use App\Listeners\WebHookEventListener;
use App\Models\Item;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\Status;
use App\Models\WebHook;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Spatie\WebhookServer\CallWebhookJob;
use Tests\TestCase;

class OrderTest extends TestCase
{
    private Order $order;

    private array $expected;
    private array $expected_summary_structure;
    private array $expected_full_structure;
    private array $expected_full_view_structure;

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

        $item_product = $this->order->products()->create([
            'product_id' => $product->getKey(),
            'quantity' => 10,
            'price' => 247.47,
        ]);

        $item_product->schemas()->create([
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
            'paid' => $this->order->isPaid(),
        ];

        $this->expected_summary_structure = [
            'code',
            'status',
            'paid',
            'created_at',
        ];

        $this->expected_full_structure = [
            'code',
            'status',
            'paid',
            'created_at',
            'shipping_method',
        ];

        $this->expected_full_view_structure = $this->expected_full_structure + ['user'];
    }

    public function testIndexUnauthorized(): void
    {
        $response = $this->getJson('/orders');
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndex($user): void
    {
        $this->$user->givePermissionTo('orders.show');

        $this
            ->actingAs($this->$user)
            ->getJson('/orders')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure(['data' => [
                0 => $this->expected_full_structure,
            ]])
            ->assertJson(['data' => [
                0 => $this->expected,
            ]]);

        $this->assertQueryCountLessThan(20);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexPerformance($user): void
    {
        $this->$user->givePermissionTo('orders.show');

        Order::factory()->count(499)->create();

        $this
            ->actingAs($this->$user)
            ->getJson('/orders?limit=500')
            ->assertOk()
            ->assertJsonCount(500, 'data');

        $this->assertQueryCountLessThan(20);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexSorted($user): void
    {
        $this->$user->givePermissionTo('orders.show');

        Order::factory()->count(30)->create();

        $this
            ->actingAs($this->$user)
            ->getJson('/orders?limit=30&sort=created_at:desc')
            ->assertOk()
            ->assertJsonCount(30, 'data');
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexSortedInvalid($user): void
    {
        $this->$user->givePermissionTo('orders.show');

        Order::factory()->count(30)->create();

        $this
            ->actingAs($this->$user)
            ->getJson('/orders?limit=30&sort=summary:desssc')
            ->assertStatus(422)
            ->assertJsonFragment([
                'errors' => [
                    ['You can\'t sort by summary field.'],
                    ['Only asc|desc sorting directions are allowed on field summary.'],
                ],
            ]);
    }

    public function testIndexUser(): void
    {
        $shipping_method = ShippingMethod::factory()->create();
        $status = Status::factory()->create();

        $order = Order::factory()->create([
            'shipping_method_id' => $shipping_method->getKey(),
            'status_id' => $status->getKey(),
        ]);

        $this->user->orders()->save($order);

        $this
            ->actingAs($this->user)
            ->json('GET', '/orders/my')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure(['data' => [
                0 => $this->expected_full_structure,
            ]])
            ->assertJson(['data' => [
                0 => [
                    'id' => $order->getKey()
                ],
            ]]);

        $this->assertQueryCountLessThan(20);
    }

    public function testIndexUserPerformance(): void
    {
        $orders = Order::factory()->count(500)->create();

        $this->user->orders()->saveMany($orders);

        $this
            ->actingAs($this->user)
            ->json('GET', '/orders/my', ['limit' => '500'])
            ->assertOk()
            ->assertJsonCount(500, 'data');

        $this->assertQueryCountLessThan(20);
    }

    public function testIndexUserUnauthenticated(): void
    {
        $order = Order::factory()->create();

        $this->user->orders()->save($order);

        $this
            ->json('GET', '/orders/my')
            ->assertStatus(404);
    }

    public function testViewUnauthorized(): void
    {
        $response = $this->getJson('/orders/id:' . $this->order->getKey());
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testView($user): void
    {
        $this->$user->givePermissionTo('orders.show_details');

        $response = $this->actingAs($this->$user)
            ->getJson('/orders/id:' . $this->order->getKey());
        $response
            ->assertOk()
            ->assertJsonFragment(['code' => $this->order->code])
            ->assertJsonStructure(['data' => $this->expected_full_view_structure]);
    }

    public function testViewSummaryUnauthorized(): void
    {
        $response = $this->getJson('/orders/' . $this->order->code);
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testViewSummary($user): void
    {
        $this->$user->givePermissionTo('orders.show_summary');

        $response = $this->actingAs($this->$user)
            ->getJson('/orders/' . $this->order->code);
        $response
            ->assertOk()
            ->assertJsonStructure(['data' => $this->expected_summary_structure])
            ->assertJson(['data' => $this->expected]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testViewOverpaid($user): void
    {
        $this->$user->givePermissionTo('orders.show_details');

        $summaryPaid = $this->order->summary * 2;

        $this->order->payments()->save(Payment::factory()->make([
            'amount' => $summaryPaid,
            'paid' => true,
        ]));

        $response = $this->actingAs($this->$user)
            ->getJson('/orders/id:' . $this->order->getKey());
        $response
            ->assertOk()
            ->assertJsonFragment([
                'paid' => true,
                'summary_paid' => $summaryPaid
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testViewOverpaidSummary($user): void
    {
        $this->$user->givePermissionTo('orders.show_summary');

        $this->order->payments()->save(Payment::factory()->make([
            'amount' => $this->order->summary * 2,
            'paid' => true,
        ]));

        $response = $this->actingAs($this->$user)
            ->getJson('/orders/' . $this->order->code);
        $response
            ->assertOk()
            ->assertJsonFragment(['paid' => true]);
    }

    public function testViewUser(): void
    {
        $shipping_method = ShippingMethod::factory()->create();
        $status = Status::factory()->create();

        $order = Order::factory()->create([
            'shipping_method_id' => $shipping_method->getKey(),
            'status_id' => $status->getKey(),
        ]);

        $this->user->orders()->save($order);

        $this->actingAs($this->user)
            ->json('GET', '/orders/my/id:' . $order->getKey())
            ->assertOk()
            ->assertJsonFragment(['code' => $order->code])
            ->assertJsonStructure(['data' => $this->expected_full_view_structure]);
    }

    public function testViewUserUnauthenticated(): void
    {
        $order = Order::factory()->create();

        $this->user->orders()->save($order);

        $this
            ->json('GET', '/orders/my/id:' . $order->getKey())
            ->assertStatus(404);
    }

    public function testUpdateOrderStatusUnauthorized(): void
    {
        Event::fake([OrderUpdatedStatus::class]);

        $status = Status::factory()->create();

        $response = $this->postJson('/orders/id:' . $this->order->getKey() . '/status', [
            'status_id' => $status->getKey(),
        ]);

        $response->assertForbidden();
        Event::assertNotDispatched(OrderUpdatedStatus::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateOrderStatus($user): void
    {
        $this->$user->givePermissionTo('orders.edit.status');

        Event::fake([OrderUpdatedStatus::class]);

        $status = Status::factory()->create();

        $this
            ->actingAs($this->$user)
            ->postJson('/orders/id:' . $this->order->getKey() . '/status', [
                'status_id' => $status->getKey(),
            ])
            ->assertOk();

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->getKey(),
            'status_id' => $status->getKey(),
        ]);

        Event::assertDispatched(OrderUpdatedStatus::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateOrderStatusCancel($user): void
    {
        $this->$user->givePermissionTo('orders.edit.status');

        $webHook = WebHook::factory()->create([
            'events' => [
                'ItemUpdatedQuantity'
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Event::fake([OrderUpdatedStatus::class, ItemUpdatedQuantity::class]);

        $item = Item::factory()->create();

        $item_product = $this->order->products[0];

        $item_product->deposits()->create([
            'item_id' => $item->getKey(),
            'quantity' => -1 * $item_product->quantity,
        ]);

        $status = Status::factory()->create([
            'cancel' => true,
        ]);

        $response = $this->actingAs($this->$user)->postJson('/orders/id:' . $this->order->getKey() . '/status', [
            'status_id' => $status->getKey(),
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('orders', [
            'id' => $this->order->getKey(),
            'status_id' => $status->getKey(),
        ]);

        Event::assertDispatched(OrderUpdatedStatus::class);
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
    public function testUpdateOrderStatusWithWebHookQueue($user): void
    {
        $this->$user->givePermissionTo('orders.edit.status');

        $webHook = WebHook::factory()->create([
            'events' => [
                'OrderUpdatedStatus'
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Event::fake([OrderUpdatedStatus::class]);

        $status = Status::factory()->create();

        $response = $this->actingAs($this->$user)->postJson('/orders/id:' . $this->order->getKey() . '/status', [
            'status_id' => $status->getKey(),
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('orders', [
            'id' => $this->order->getKey(),
            'status_id' => $status->getKey(),
        ]);
        Event::assertDispatched(OrderUpdatedStatus::class);

        Queue::fake();

        $order = Order::find($this->order->getKey());
        $event = new OrderUpdatedStatus($order);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Queue::assertPushed(CallWebhookJob::class, function ($job) use ($webHook, $order) {
            $payload = $job->payload;
            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $order->getKey()
                && $payload['data_type'] === 'Order'
                && $payload['event'] === 'OrderUpdatedStatus';
        });
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateOrderStatusWithWebHookDispatched($user): void
    {
        $this->$user->givePermissionTo('orders.edit.status');

        $webHook = WebHook::factory()->create([
            'events' => [
                'OrderUpdatedStatus'
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Event::fake([OrderUpdatedStatus::class]);

        $status = Status::factory()->create();

        $response = $this->actingAs($this->$user)->postJson('/orders/id:' . $this->order->getKey() . '/status', [
            'status_id' => $status->getKey(),
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('orders', [
            'id' => $this->order->getKey(),
            'status_id' => $status->getKey(),
        ]);

        Event::assertDispatched(OrderUpdatedStatus::class);

        Bus::fake();

        $order = Order::find($this->order->getKey());
        $event = new OrderUpdatedStatus($order);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $order) {
            $payload = $job->payload;
            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $order->getKey()
                && $payload['data_type'] === 'Order'
                && $payload['event'] === 'OrderUpdatedStatus';
        });
    }

    /**
     * @dataProvider authProvider
     */
    public function testViewUnderpaid($user): void
    {
        $this->$user->givePermissionTo('orders.show_details');

        $summaryPaid = $this->order->summary / 2;

        $this->order->payments()->save(Payment::factory()->make([
            'amount' => $summaryPaid,
            'paid' => true,
        ]));

        $response = $this->actingAs($this->$user)
            ->getJson('/orders/id:' . $this->order->getKey());
        $response
            ->assertOk()
            ->assertJsonFragment([
                'paid' => false,
                'summary_paid' => $summaryPaid,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testViewUnderpaidSummary($user): void
    {
        $this->$user->givePermissionTo('orders.show_summary');

        $this->order->payments()->save(Payment::factory()->make([
            'amount' => $this->order->summary / 2,
            'paid' => true,
        ]));

        $response = $this->actingAs($this->$user)
            ->getJson('/orders/' . $this->order->code);
        $response
            ->assertOk()
            ->assertJsonFragment(['paid' => false]);
    }

    public function testViewCreatedByUser(): void
    {
        $this->user->givePermissionTo(['orders.add', 'orders.show_details']);

        $shippingMethod = ShippingMethod::factory()->create();
        $product = Product::factory()->create([
            'public' => true,
        ]);

        Event::fake([OrderCreated::class]);

        $response = $this->actingAs($this->user)->json('POST', '/orders', [
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
            'items' => [
                [
                    'product_id' => $product->getKey(),
                    'quantity' => 1,
                ]
            ],
        ]);

        Event::assertDispatched(OrderCreated::class);

        $order = Order::find($response->getData()->data->id);

        $response = $this->actingAs($this->user)
            ->getJson('/orders/id:' . $order->getKey());
        $response
            ->assertOk()
            ->assertJsonFragment([
                'email' => $this->user->email,
                'id' => $this->user->getKey(),
            ])
            ->assertJsonStructure(['data' => $this->expected_full_view_structure]);
    }
}
