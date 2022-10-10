<?php

namespace Tests\Feature;

use App\Enums\DiscountTargetType;
use App\Enums\DiscountType;
use App\Enums\MetadataType;
use App\Enums\ValidationError;
use App\Events\ItemUpdatedQuantity;
use App\Events\OrderCreated;
use App\Events\OrderUpdatedStatus;
use App\Listeners\WebHookEventListener;
use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Discount;
use App\Models\Item;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PriceRange;
use App\Models\Product;
use App\Models\ProductSet;
use App\Models\Schema;
use App\Models\ShippingMethod;
use App\Models\Status;
use App\Models\Tag;
use App\Models\User;
use App\Models\WebHook;
use App\Services\Contracts\OrderServiceContract;
use App\Services\OrderService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\Fluent\AssertableJson;
use Spatie\WebhookServer\CallWebhookJob;
use Tests\TestCase;

class OrderTest extends TestCase
{
    private Order $order;
    private ShippingMethod $shippingMethod;
    private array $expected;
    private array $expected_summary_structure;
    private array $expected_full_structure;
    private array $expected_full_view_structure;

    public function setUp(): void
    {
        parent::setUp();

        Product::factory()->create();

        $this->shippingMethod = ShippingMethod::factory()->create();
        $status = Status::factory()->create();
        $product = Product::factory()->create();

        $this->order = Order::factory()->create([
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'status_id' => $status->getKey(),
        ]);

        $item_product = $this->order->products()->create([
            'product_id' => $product->getKey(),
            'quantity' => 10,
            'price' => 247.47,
            'price_initial' => 247.47,
            'name' => $product->name,
        ]);

        $item_product->schemas()->create([
            'name' => 'Grawer',
            'value' => 'HESEYA',
            'price' => 49.99,
            'price_initial' => 49.99,
        ]);

        $this->order->metadata()->create([
            'name' => 'Metadata',
            'value' => 'metadata test',
            'value_type' => MetadataType::STRING,
            'public' => true,
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
            'comment',
            'email',
            'cart_total_initial',
            'cart_total',
            'shipping_price_initial',
            'shipping_price',
            'summary',
            'summary_paid',
            'currency',
            'delivery_address',
            'metadata',
        ];

        $this->expected_full_view_structure = $this->expected_full_structure + [
            'buyer',
            'products',
            'payments',
            'discounts',
        ];
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
            ],
            ])
            ->assertJson(['data' => [
                0 => $this->expected,
            ],
            ]);

        $this->assertQueryCountLessThan(20);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexPerformance($user): void
    {
        $this->$user->givePermissionTo('orders.show');
        $status = Status::factory()->create();

        Order::factory()->count(499)->create([
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'status_id' => $status->getKey(),
        ]);

        $this
            ->actingAs($this->$user)
            ->getJson('/orders?limit=500')
            ->assertOk()
            ->assertJsonCount(500, 'data');

        $this->assertQueryCountLessThan(21);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexSorted($user): void
    {
        $this->$user->givePermissionTo('orders.show');

        $status = Status::factory()->create();

        Order::factory()->count(30)->create([
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'status_id' => $status->getKey(),
        ]);

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
            ->assertJsonFragment(['key' => ValidationError::IN]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexUser($user): void
    {
        $this->$user->givePermissionTo('orders.show_own');

        $status = Status::factory()->create();

        $order = Order::factory()->create([
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'status_id' => $status->getKey(),
        ]);

        $this->$user->orders()->save($order);

        $another_user = User::factory()->create();

        $order_another_user = Order::factory()->create([
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'status_id' => $status->getKey(),
        ]);

        $another_user->orders()->save($order_another_user);

        $order_no_user = Order::factory()->create([
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'status_id' => $status->getKey(),
        ]);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/orders/my')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure(['data' => [
                0 => $this->expected_full_structure,
            ],
            ])
            ->assertJson(['data' => [
                0 => [
                    'id' => $order->getKey(),
                ],
            ],
            ])
            ->assertJsonMissing([
                'id' => $order_another_user->getKey(),
            ])
            ->assertJsonMissing([
                'id' => $order_no_user->getKey(),
            ]);

        $this->assertQueryCountLessThan(20);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexUserPerformance($user): void
    {
        $this->$user->givePermissionTo('orders.show_own');

        $status = Status::factory()->create();

        $orders = Order::factory()->count(500)->create([
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'status_id' => $status->getKey(),
        ]);

        $this->$user->orders()->saveMany($orders);

        $this
            ->actingAs($this->$user)
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

        Order::factory([
            'status_id' => $status->getKey(),
        ])->create();

        $this
            ->actingAs($this->$user)
            ->json('GET', '/orders')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure(['data' => [
                0 => $this->expected_full_structure,
            ],
            ])
            ->assertJson(['data' => [
                0 => $this->expected,
            ],
            ]);

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
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'status_id' => $status->getKey(),
        ])->create();

        $this
            ->actingAs($this->$user)
            ->json('GET', '/orders', ['status_id' => $status->getKey()])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure(['data' => [
                0 => $this->expected_full_structure,
            ],
            ])
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
                ],
            ],
            ]);

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
     * @dataProvider booleanProvider
     */
    public function testIndexSearchByPaid($user, $boolean, $booleanValue): void
    {
        $this->$user->givePermissionTo('orders.show');

        $product = Product::factory()->create([
            'public' => true,
        ]);
        $status = Status::factory()->create();

        $order1 = Order::factory([
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'status_id' => $status->getKey(),
        ])->create();

        $order1->products()->create([
            'product_id' => $product->getKey(),
            'quantity' => 10,
            'price' => 247.47,
            'price_initial' => 247.47,
            'name' => $product->name,
        ]);

        $order1->refresh();
        $order1->payments()->create([
            'method' => 'payu',
            'amount' => $order1->summary,
            'paid' => true,
        ]);

        $orderId = $booleanValue ? $order1->getKey() : $this->order->getKey();

        $this
            ->actingAs($this->$user)
            ->json('GET', '/orders', ['paid' => $boolean])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $orderId,
            ]);
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
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'status_id' => $status->getKey(),
            'created_at' => Carbon::yesterday(),
        ])->create();

        $order2 = Order::factory([
            'shipping_method_id' => $this->shippingMethod->getKey(),
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
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'status_id' => $status->getKey(),
            'created_at' => Carbon::yesterday(),
        ])->create();

        Order::factory([
            'shipping_method_id' => $this->shippingMethod->getKey(),
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
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'status_id' => $status->getKey(),
            'created_at' => Carbon::yesterday(),
        ])->create();

        Order::factory([
            'shipping_method_id' => $this->shippingMethod->getKey(),
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

    /**
     * @dataProvider authProvider
     */
    public function testViewWrongId($user): void
    {
        $this->$user->givePermissionTo('orders.show_details');

        $response = $this->actingAs($this->$user)
            ->getJson('/orders/id:its-not-uuid')
            ->assertNotFound();

        $this->assertEquals(404, $response->getData()->error->code); //get error code from our error handle structure

        $response = $this->actingAs($this->$user)
            ->getJson('/orders/id:' . $this->order->getKey() . $this->order->getKey())
            ->assertNotFound();

        $this->assertEquals(404, $response->getData()->error->code);
        $this->order->delete();

        $response = $this->actingAs($this->$user)
            ->getJson('/orders/id:' . $this->order->getKey())
            ->assertNotFound();

        $this->assertEquals(404, $response->getData()->error->code);
    }

    /**
     * @dataProvider authProvider
     */
    public function testViewPrivateMetadata($user): void
    {
        $this->$user->givePermissionTo(['orders.show_details', 'orders.show_metadata_private']);

        $privateMetadata = $this->order->metadataPrivate()->create([
            'name' => 'hiddenMetadata',
            'value' => 'hidden metadata test',
            'value_type' => MetadataType::STRING,
            'public' => false,
        ]);

        $response = $this->actingAs($this->$user)
            ->getJson('/orders/id:' . $this->order->getKey());
        $response
            ->assertOk()
            ->assertJsonFragment(['code' => $this->order->code])
            ->assertJsonStructure(['data' => $this->expected_full_view_structure])
            ->assertJsonFragment(['metadata_private' => [
                $privateMetadata->name => $privateMetadata->value,
            ],
            ]);
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
    public function testViewSummaryWrongCode($user): void
    {
        $this->$user->givePermissionTo('orders.show_summary');

        $response = $this->actingAs($this->$user)
            ->getJson('/orders/its_wrong_code')
            ->assertNotFound();

        $this->assertEquals(404, $response->getData()->error->code); //get error code from our error handle structure

        $response = $this->actingAs($this->$user)
            ->getJson('/orders/' . $this->order->code . '_' . $this->order->code)
            ->assertNotFound();

        $this->assertEquals(404, $response->getData()->error->code);
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

    /**
     * @dataProvider authProvider
     */
    public function testViewUser($user): void
    {
        $this->$user->givePermissionTo('orders.show_own');

        $status = Status::factory()->create();

        $order = Order::factory()->create([
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'status_id' => $status->getKey(),
        ]);

        $this->$user->orders()->save($order);

        $this->actingAs($this->$user)
            ->json('GET', '/orders/my/' . $order->code)
            ->assertOk()
            ->assertJsonFragment([
                'id' => $order->getKey(),
                'code' => $order->code,
            ])
            ->assertJsonStructure(['data' => $this->expected_full_view_structure]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testViewUserWrongCode($user): void
    {
        $this->$user->givePermissionTo('orders.show_own');

        $shipping_method = ShippingMethod::factory()->create();
        $status = Status::factory()->create();

        $order = Order::factory()->create([
            'shipping_method_id' => $shipping_method->getKey(),
            'status_id' => $status->getKey(),
        ]);

        $this->$user->orders()->save($order);

        $this->actingAs($this->$user)
            ->json('GET', '/orders/my/its_wrong_code')
            ->assertNotFound();

        $this->actingAs($this->$user)
            ->json('GET', '/orders/my/' . $order->code . '_' . $order->code)
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testViewUserOrderNoUser($user): void
    {
        $this->$user->givePermissionTo('orders.show_own');

        $order = Order::factory()->create();

        $this->actingAs($this->$user)
            ->json('GET', '/orders/my/' . $order->code)
            ->assertStatus(404);
    }

    /**
     * @dataProvider authProvider
     */
    public function testViewUserOrderAnotherUser($user): void
    {
        $this->$user->givePermissionTo('orders.show_own');

        $another_user = User::factory()->create();

        $order = Order::factory()->create();

        $another_user->orders()->save($order);

        $this->actingAs($this->$user)
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

    /**
     * @dataProvider authProvider
     */
    public function testViewOrderDiscounts($user): void
    {
        $this->$user->givePermissionTo('orders.show_details');

        $status = Status::factory()->create();
        $product = Product::factory()->create();
        $product2 = Product::factory()->create();

        $order = Order::factory()->create([
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'status_id' => $status->getKey(),
            'cart_total_initial' => 394.94,
            'cart_total' => 300.00,
            'summary' => 300.00,
            'shipping_price' => 0,
        ]);

        $item_product = $order->products()->create([
            'product_id' => $product->getKey(),
            'quantity' => 1,
            'price' => 200.00,
            'price_initial' => 247.47,
            'name' => $product->name,
        ]);

        $item_product2 = $order->products()->create([
            'product_id' => $product2->getKey(),
            'quantity' => 1,
            'price' => 100.00,
            'price_initial' => 147.47,
            'name' => $product2->name,
        ]);

        $discountShipping = Discount::factory()->create([
            'description' => 'Testowy kupon',
            'code' => 'S43SA2',
            'value' => 100,
            'type' => DiscountType::PERCENTAGE,
            'target_type' => DiscountTargetType::SHIPPING_PRICE,
            'target_is_allow_list' => true,
        ]);

        $order->discounts()->attach(
            $discountShipping->getKey(),
            [
                'name' => $discountShipping->name,
                'type' => $discountShipping->type,
                'value' => $discountShipping->value,
                'target_type' => $discountShipping->target_type,
                'applied_discount' => $order->shipping_price_initial,
                'code' => $discountShipping->code,
            ],
        );

        $discountProduct = Discount::factory()->create([
            'description' => 'Testowy kupon',
            'code' => '2AS34S',
            'value' => 47.47,
            'type' => DiscountType::AMOUNT,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ]);

        $discountProduct->products()->attach($product);
        $discountProduct->products()->attach($product2);

        $item_product->discounts()->attach(
            $discountProduct->getKey(),
            [
                'name' => $discountProduct->name,
                'type' => $discountProduct->type,
                'value' => $discountProduct->value,
                'target_type' => $discountProduct->target_type,
                'applied_discount' => $discountProduct->value,
                'code' => $discountProduct->code,
            ],
        );

        $item_product2->discounts()->attach(
            $discountProduct->getKey(),
            [
                'name' => $discountProduct->name,
                'type' => $discountProduct->type,
                'value' => $discountProduct->value,
                'target_type' => $discountProduct->target_type,
                'applied_discount' => $discountProduct->value,
                'code' => $discountProduct->code,
            ],
        );

        $this->actingAs($this->$user)
            ->json('GET', '/orders/id:' . $order->getKey())
            ->assertJson(function (AssertableJson $json) use ($discountShipping, $discountProduct): void {
                $json
                    ->has('data', function ($json) use ($discountShipping, $discountProduct): void {
                        $json
                            // order has 1 discount AND 1 discount same for two products
                            ->has('discounts', function ($json) use ($discountShipping, $discountProduct): void {
                                $json
                                    ->has(2)
                                    ->where('0.code', $discountShipping->code)
                                    ->where('1.code', $discountProduct->code)
                                    ->where('1.applied_discount', 94.94);
                            })
                            // order product has 1 discounts
                            ->has('products', function ($json) use ($discountProduct): void {
                                $json
                                    ->has(2)
                                    ->first(function ($json) use ($discountProduct): void {
                                        $json
                                            ->has('discounts', function ($json) use ($discountProduct): void {
                                                $json
                                                    ->has(1)
                                                    ->first(function ($json) use ($discountProduct): void {
                                                        $json
                                                            ->where('code', $discountProduct->code)
                                                            ->etc();
                                                    });
                                            })
                                            ->etc();
                                    })
                                    ->etc();
                            })
                            ->etc();
                    })
                    ->etc();
            });
    }

    /**
     * @dataProvider authProvider
     */
    public function testViewPerformance($user): void
    {
        $this->$user->givePermissionTo('orders.show_details');

        $response = $this->actingAs($this->$user)
            ->getJson('/orders/id:' . $this->order->getKey());
        $response
            ->assertOk()
            ->assertJsonFragment(['code' => $this->order->code])
            ->assertJsonStructure(['data' => $this->expected_full_view_structure]);
        $this->assertQueryCountLessThan(1);
    }

    /**
     * @dataProvider authProvider
     */
    public function testViewPerformanceWithDiscounts($user): void
    {
        $this->$user->givePermissionTo('orders.show_details');

        $attribute = Attribute::factory()->create();
        AttributeOption::factory()->count(2)->create([
            'attribute_id' => $attribute->getKey(),
            'index' => 1,
        ]);

        $status = Status::factory()->create();
        $status->metadata()->create([
            'name' => 'Metadata',
            'value' => 'metadata test',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);
        $status->metadata()->create([
            'name' => 'Metadata private',
            'value' => 'metadata test',
            'value_type' => MetadataType::STRING,
            'public' => false,
        ]);

        $tag = Tag::factory()->create();
        $set = ProductSet::factory()->create([
            'public' => true,
            'hide_on_index' => false,
        ]);

        $productItem = Item::factory()->create();

        $product = Product::factory()->create();
        $product->items()->sync([$productItem->getKey()]);
        $product->attributes()->attach($attribute->getKey());
        $product->metadata()->create([
            'name' => 'Metadata',
            'value' => 'metadata test',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);
        $product->metadata()->create([
            'name' => 'Metadata private',
            'value' => 'metadata test',
            'value_type' => MetadataType::STRING,
            'public' => false,
        ]);
        $product->tags()->sync($tag->getKey());
        $product->sets()->sync($set->getKey());

        $product2 = Product::factory()->create();
        $product2->items()->sync([$productItem->getKey()]);
        $product2->metadata()->create([
            'name' => 'Metadata',
            'value' => 'metadata test',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);
        $product2->metadata()->create([
            'name' => 'Metadata private',
            'value' => 'metadata test',
            'value_type' => MetadataType::STRING,
            'public' => false,
        ]);
        $product2->tags()->sync($tag->getKey());
        $product2->sets()->sync($set->getKey());

        $schema = Schema::factory()->create([
            'type' => 'select',
            'price' => 0,
            'hidden' => false,
            'required' => true,
        ]);
        $product->schemas()->sync([$schema->getKey()]);
        $product2->schemas()->sync([$schema->getKey()]);

        $option = $schema->options()->create([
            'name' => 'XL',
            'price' => 0,
        ]);
        $item = Item::factory()->create();
        $option->items()->sync([$item->getKey()]);

        $lowRange = PriceRange::create(['start' => 0]);
        $lowRange->prices()->create([
            'value' => rand(8, 15) + (rand(0, 99) / 100),
        ]);

        $highRange = PriceRange::create(['start' => 210]);
        $highRange->prices()->create(['value' => 0.0]);

        $this->shippingMethod->priceRanges()->saveMany([$lowRange, $highRange]);

        $order = Order::factory()->create([
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'status_id' => $status->getKey(),
            'cart_total_initial' => 394.94,
            'cart_total' => 300.00,
            'summary' => 300.00,
            'shipping_price' => 0,
        ]);

        $order->metadata()->create([
            'name' => 'Metadata',
            'value' => 'metadata test',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        $order->metadata()->create([
            'name' => 'Metadata private',
            'value' => 'metadata test',
            'value_type' => MetadataType::STRING,
            'public' => false,
        ]);

        $discountShipping = Discount::factory()->create([
            'description' => 'Testowy kupon',
            'code' => 'S43SA2',
            'value' => 100,
            'type' => DiscountType::PERCENTAGE,
            'target_type' => DiscountTargetType::SHIPPING_PRICE,
            'target_is_allow_list' => true,
        ]);

        $discountShipping->shippingMethods()->attach($this->shippingMethod);

        $order->discounts()->attach(
            $discountShipping->getKey(),
            [
                'name' => $discountShipping->name,
                'type' => $discountShipping->type,
                'value' => $discountShipping->value,
                'target_type' => $discountShipping->target_type,
                'applied_discount' => $order->shipping_price_initial,
                'code' => $discountShipping->code,
            ],
        );

        $discountProduct = Discount::factory()->create([
            'description' => 'Testowy kupon',
            'code' => null,
            'value' => 47.47,
            'type' => DiscountType::AMOUNT,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ]);

        $discountProduct->products()->attach($product);
        $discountProduct->products()->attach($product2);

        $discountProduct2 = Discount::factory()->create([
            'description' => 'Testowy kupon 2',
            'code' => 'O213D12',
            'value' => 10.00,
            'type' => DiscountType::AMOUNT,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ]);

        $discountProduct2->products()->attach($product);
        $discountProduct2->products()->attach($product2);

        $sale = Discount::factory()->create([
            'description' => 'Promocja na wszystko',
            'code' => null,
            'value' => 10.00,
            'type' => DiscountType::AMOUNT,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ]);

        $sale->productSets()->attach($set);
        $sale->shippingMethods()->attach($this->shippingMethod);
        $sale->products()->attach($product);
        $sale->products()->attach($product2);

        $product->sales()->attach($sale);
        $product2->sales()->attach($sale);

        $item_product = $order->products()->create([
            'product_id' => $product->getKey(),
            'quantity' => 1,
            'price' => 200.00,
            'price_initial' => 247.47,
            'name' => $product->name,
        ]);

        $item_product->discounts()->attach([
            $discountProduct->getKey() => [
                'name' => $discountProduct->name,
                'type' => $discountProduct->type,
                'value' => $discountProduct->value,
                'target_type' => $discountProduct->target_type,
                'applied_discount' => $discountProduct->value,
                'code' => $discountProduct->code,
            ],
            $discountProduct2->getKey() => [
                'name' => $discountProduct2->name,
                'type' => $discountProduct2->type,
                'value' => $discountProduct2->value,
                'target_type' => $discountProduct2->target_type,
                'applied_discount' => $discountProduct2->value,
                'code' => $discountProduct2->code,
            ],
        ]);

        $item_product2 = $order->products()->create([
            'product_id' => $product2->getKey(),
            'quantity' => 1,
            'price' => 100.00,
            'price_initial' => 147.47,
            'name' => $product2->name,
        ]);

        $item_product2->discounts()->attach([
            $discountProduct->getKey() => [
                'name' => $discountProduct->name,
                'type' => $discountProduct->type,
                'value' => $discountProduct->value,
                'target_type' => $discountProduct->target_type,
                'applied_discount' => $discountProduct->value,
                'code' => $discountProduct->code,
            ],
            $discountProduct2->getKey() => [
                'name' => $discountProduct2->name,
                'type' => $discountProduct2->type,
                'value' => $discountProduct2->value,
                'target_type' => $discountProduct2->target_type,
                'applied_discount' => $discountProduct2->value,
                'code' => $discountProduct2->code,
            ],
        ]);

        $response = $this->actingAs($this->$user)
            ->json('GET', '/orders/id:' . $order->getKey())->assertOk();

//        dd($response->json());
//        dd(DB::getQueryLog());

        $this->assertQueryCountLessThan(1);
    }

    public function testUpdateOrderStatusUnauthorized(): void
    {
        Event::fake([OrderUpdatedStatus::class]);

        $status = Status::factory()->create();

        $response = $this->patchJson('/orders/id:' . $this->order->getKey() . '/status', [
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
            ->patchJson('/orders/id:' . $this->order->getKey() . '/status', [
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
            ->patchJson('/orders/id:' . $this->order->getKey() . '/status', [
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

        $response = $this->actingAs($this->$user)->patchJson('/orders/id:' . $this->order->getKey() . '/status', [
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

        $response = $this->actingAs($this->$user)->patchJson('/orders/id:' . $this->order->getKey() . '/status', [
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

        $response = $this->actingAs($this->$user)->patchJson('/orders/id:' . $this->order->getKey() . '/status', [
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

        $product = Product::factory()->create([
            'public' => true,
        ]);

        Event::fake([OrderCreated::class]);

        $response = $this->actingAs($this->user)->json('POST', '/orders', [
            'email' => 'test@example.com',
            'shipping_method_id' => $this->shippingMethod->getKey(),
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
    public function testOrderHasUser($user): void
    {
        $this->$user->givePermissionTo(['orders.add']);

        $product = Product::factory()->create([
            'public' => true,
        ]);

        Event::fake([OrderCreated::class]);

        $this->actingAs($this->$user)->json('POST', '/orders', [
            'email' => 'test@example.com',
            'shipping_method_id' => $this->shippingMethod->getKey(),
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
            'buyer_id' => $this->$user->getKey(),
        ]);
    }
}
