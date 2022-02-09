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
use App\Models\User;
use App\Models\WebHook;
use App\Services\Contracts\OrderServiceContract;
use App\Services\OrderService;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
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

        /** @var OrderService $orderService */
        $orderService = App::make(OrderServiceContract::class);

        $this->order->update([
            'summary' => $orderService->calcSummary($this->order),
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
                'hidden' => $status->hidden,
                'no_notifications' => $status->no_notifications,
            ],
            'paid' => $this->order->paid,
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
            ->getJson('/orders?limit=30&sort=currency:desssc')
            ->assertStatus(422)
            ->assertJsonFragment([
                'errors' => [
                    ['You can\'t sort by currency field.'],
                    ['Only asc|desc sorting directions are allowed on field currency.'],
                ],
            ]);
    }

    public function testIndexUser(): void
    {
        $this->user->givePermissionTo('orders.show_own');

        $shipping_method = ShippingMethod::factory()->create();
        $status = Status::factory()->create();

        $order = Order::factory()->create([
            'shipping_method_id' => $shipping_method->getKey(),
            'status_id' => $status->getKey(),
        ]);

        $this->user->orders()->save($order);

        $another_user = User::factory()->create();

        $order_another_user = Order::factory()->create([
            'shipping_method_id' => $shipping_method->getKey(),
            'status_id' => $status->getKey(),
        ]);

        $another_user->orders()->save($order_another_user);

        $order_no_user = Order::factory()->create([
            'shipping_method_id' => $shipping_method->getKey(),
            'status_id' => $status->getKey(),
        ]);

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
                    'id' => $order->getKey(),
                ],
            ]])
            ->assertJsonMissing([
                'id' => $order_another_user->getKey(),
            ])
            ->assertJsonMissing([
                'id' => $order_no_user->getKey(),
            ]);

        $this->assertQueryCountLessThan(20);
    }

    public function testIndexUserPerformance(): void
    {
        $this->user->givePermissionTo('orders.show_own');

        $orders = Order::factory()->count(500)->create();

        $this->user->orders()->saveMany($orders);

        $this
            ->actingAs($this->user)
            ->json('GET', '/orders/my', ['limit' => '500'])
            ->assertOk()
            ->assertJsonCount(500, 'data');

        $this->assertQueryCountLessThan(20);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexWithHiddenStatus($user): void
    {
        $this->$user->givePermissionTo('orders.show');

        $status = Status::factory([
            'hidden' => true,
        ])->create();

        $order = Order::factory([
            'status_id' => $status->getKey(),
        ])->create();

        $this
            ->actingAs($this->$user)
            ->json('GET', '/orders')
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
    public function testIndexFilterByHiddenStatus($user): void
    {
        $this->$user->givePermissionTo('orders.show');

        $status = Status::factory([
            'hidden' => true,
        ])->create();

        $order = Order::factory([
            'status_id' => $status->getKey(),
        ])->create();

        $this
            ->actingAs($this->$user)
            ->json('GET', '/orders', ['status_id' => $status->getKey()])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure(['data' => [
                0 => $this->expected_full_structure,
            ]])
            ->assertJson(['data' => [
                0 => [
                    'code' => $order->code,
                    'status' => [
                        'id' => $status->getKey(),
                        'name' => $status->name,
                        'color' => $status->color,
                        'description' => $status->description,
                        'hidden' => $status->hidden,
                        'no_notifications' => $status->no_notifications,
                    ],
                ]]]);

        $this->assertQueryCountLessThan(20);
    }

    public function testIndexUserUnauthenticated(): void
    {
        $order = Order::factory()->create();

        $this->user->orders()->save($order);

        $this
            ->json('GET', '/orders/my')
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexSearchByPaid($user): void
    {
        $this->$user->givePermissionTo('orders.show');

        $product = Product::factory()->create([
            'public' => true,
        ]);
        $status = Status::factory()->create();

        $order1 = Order::factory([
            'status_id' => $status->getKey(),
        ])->create();

        $order1->products()->create([
            'product_id' => $product->getKey(),
            'quantity' => 10,
            'price' => 247.47,
        ]);

        $order1->refresh();
        $order1->payments()->create([
            'method' => 'payu',
            'amount' => $order1->summary,
            'paid' => true,
        ]);

        $order2 = Order::factory([
            'status_id' => $status->getKey(),
        ])->create();

        $order2->products()->create([
            'product_id' => $product->getKey(),
            'quantity' => 10,
            'price' => 247.47,
        ]);

        $this
            ->actingAs($this->$user)
            ->getJson('/orders?paid=1')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexSearchByFrom($user): void
    {
        $this->$user->givePermissionTo('orders.show');

        $status = Status::factory()->create();

        $from = $this->order->created_at;

        Order::factory([
            'status_id' => $status->getKey(),
            'created_at' => Carbon::yesterday(),
        ])->create();

        $order2 = Order::factory([
            'status_id' => $status->getKey(),
            'created_at' => Carbon::tomorrow(),
        ])->create();

        $response = $this
            ->actingAs($this->$user)
            ->json('GET', '/orders', [
                'from' => $from,
            ]);

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $this->order->getKey()])
            ->assertJsonFragment(['id' => $order2->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexSearchByTo($user): void
    {
        $this->$user->givePermissionTo('orders.show');

        $status = Status::factory()->create();

        $to = $this->order->created_at;

        $order1 = Order::factory([
            'status_id' => $status->getKey(),
            'created_at' => Carbon::yesterday(),
        ])->create();

        Order::factory([
            'status_id' => $status->getKey(),
            'created_at' => Carbon::tomorrow(),
        ])->create();

        $response = $this
            ->actingAs($this->$user)
            ->json('GET', '/orders', [
                'to' => $to,
            ]);

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $this->order->getKey()])
            ->assertJsonFragment(['id' => $order1->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexSearchByFromTo($user): void
    {
        $this->$user->givePermissionTo('orders.show');

        $status = Status::factory()->create();

        $from = Carbon::yesterday()->addHour();
        $to = Carbon::tomorrow()->subHour();

        Order::factory([
            'status_id' => $status->getKey(),
            'created_at' => Carbon::yesterday(),
        ])->create();

        Order::factory([
            'status_id' => $status->getKey(),
            'created_at' => Carbon::tomorrow(),
        ])->create();

        $response = $this
            ->actingAs($this->$user)
            ->json('GET', '/orders', [
                'from' => $from,
                'to' => $to,
            ]);

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $this->order->getKey()]);
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
                'summary_paid' => $summaryPaid,
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
        $this->user->givePermissionTo('orders.show_own');

        $shipping_method = ShippingMethod::factory()->create();
        $status = Status::factory()->create();

        $order = Order::factory()->create([
            'shipping_method_id' => $shipping_method->getKey(),
            'status_id' => $status->getKey(),
        ]);

        $this->user->orders()->save($order);

        $this->actingAs($this->user)
            ->json('GET', '/orders/my/' . $order->code)
            ->assertOk()
            ->assertJsonFragment([
                'id' => $order->getKey(),
                'code' => $order->code,
            ])
            ->assertJsonStructure(['data' => $this->expected_full_view_structure]);
    }

    public function testViewUserOrderNoUser(): void
    {
        $this->user->givePermissionTo('orders.show_own');

        $order = Order::factory()->create();

        $this->actingAs($this->user)
            ->json('GET', '/orders/my/' . $order->code)
            ->assertStatus(404);
    }

    public function testViewUserOrderAnotherUser(): void
    {
        $this->user->givePermissionTo('orders.show_own');

        $another_user = User::factory()->create();

        $order = Order::factory()->create();

        $another_user->orders()->save($order);

        $this->actingAs($this->user)
            ->json('GET', '/orders/my/' . $order->code)
            ->assertStatus(404);
    }

    public function testViewUserUnauthenticated(): void
    {
        $order = Order::factory()->create();

        $this->user->orders()->save($order);

        $this
            ->json('GET', '/orders/my/' . $order->code)
            ->assertForbidden();
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
    public function testUpdateOrderStatusNoNotifications($user): void
    {
        $this->$user->givePermissionTo('orders.edit.status');

        Notification::fake();

        $status = Status::factory([
            'no_notifications' => true,
        ])->create();

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

        Notification::assertNothingSent();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateOrderStatusCancel($user): void
    {
        $this->$user->givePermissionTo('orders.edit.status');

        $webHook = WebHook::factory()->create([
            'events' => [
                'ItemUpdatedQuantity',
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
                'OrderUpdatedStatus',
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
                'OrderUpdatedStatus',
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
                ],
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

    /**
     * @dataProvider authProvider
     */
    public function testOrderHasUser($user)
    {
        $this->$user->givePermissionTo(['orders.add']);

        $shippingMethod = ShippingMethod::factory()->create();
        $product = Product::factory()->create([
            'public' => true,
        ]);

        Event::fake([OrderCreated::class]);

        $this->actingAs($this->$user)->json('POST', '/orders', [
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
                ],
            ],
        ]);

        $this->assertDatabaseHas('orders', [
            'user_id' => Auth::user()->getKey(),
        ]);
    }
}
