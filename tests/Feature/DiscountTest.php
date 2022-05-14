<?php

namespace Tests\Feature;

use App\Enums\ConditionType;
use App\Enums\DiscountTargetType;
use App\Enums\DiscountType;
use App\Enums\ValidationError;
use App\Events\CouponCreated;
use App\Events\CouponDeleted;
use App\Events\CouponUpdated;
use App\Events\SaleCreated;
use App\Events\SaleDeleted;
use App\Events\SaleUpdated;
use App\Listeners\WebHookEventListener;
use App\Models\ConditionGroup;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductSet;
use App\Models\Role;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Models\WebHook;
use App\Services\Contracts\DiscountServiceContract;
use Carbon\Carbon;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Spatie\WebhookServer\CallWebhookJob;
use Tests\TestCase;

class DiscountTest extends TestCase
{
    use WithFaker;

    private array $conditions;
    private $role;
    private $conditionUser;
    private $conditionProduct;
    private $conditionProductSet;
    private array $expectedStructure;

    public function setUp(): void
    {
        parent::setUp();

        // kupony
        Discount::factory()->count(10)->create();
        // promocje
        Discount::factory([
            'code' => null,
            'target_type' => DiscountTargetType::ORDER_VALUE,
        ])->count(10)->create();

        $this->role = Role::factory()->create();
        $this->conditionUser = User::factory()->create();
        $this->conditionProduct = Product::factory()->create();
        $this->conditionProductSet = ProductSet::factory()->create();

        $this->conditions = [
            [
                'type' => ConditionType::ORDER_VALUE,
                'min_value' => 100,
                'max_value' => 500,
                'include_taxes' => false,
                'is_in_range' => true,
            ],
            [
                'type' => ConditionType::USER_IN_ROLE,
                'roles' => [
                    $this->role->getKey(),
                ],
                'is_allow_list' => true,
            ],
            [
                'type' => ConditionType::USER_IN,
                'users' => [
                    $this->conditionUser->getKey(),
                ],
                'is_allow_list' => true,
            ],
            [
                'type' => ConditionType::PRODUCT_IN_SET,
                'product_sets' => [
                    $this->conditionProductSet->getKey(),
                ],
                'is_allow_list' => true,
            ],
            [
                'type' => ConditionType::PRODUCT_IN,
                'products' => [
                    $this->conditionProduct->getKey(),
                ],
                'is_allow_list' => true,
            ],
            [
                'type' => ConditionType::DATE_BETWEEN,
                'start_at' => Carbon::now(),
                'end_at' => Carbon::tomorrow(),
                'is_in_range' => true,
            ],
            [
                'type' => ConditionType::TIME_BETWEEN,
                'start_at' => Carbon::now()->toTimeString(),
                'end_at' => Carbon::tomorrow()->toTimeString(),
                'is_in_range' => true,
            ],
            [
                'type' => ConditionType::MAX_USES,
                'max_uses' => 150,
            ],
            [
                'type' => ConditionType::MAX_USES_PER_USER,
                'max_uses' => 5,
            ],
            [
                'type' => ConditionType::WEEKDAY_IN,
                'weekday' => [false, true, false, false, true, true, false],
            ],
            [
                'type' => ConditionType::CART_LENGTH,
                'min_value' => 1,
                'max_value' => 100,
            ],
            [
                'type' => ConditionType::COUPONS_COUNT,
                'min_value' => 1,
                'max_value' => 10,
            ],
        ];

        $this->expectedStructure = [
            'data' => [
                'id',
                'name',
                'description',
                'value',
                'type',
                'priority',
                'uses',
                'condition_groups',
                'target_type',
                'target_products',
                'target_sets',
                'target_shipping_methods',
                'target_is_allow_list',
                'metadata',
            ],
        ];
    }

    public function couponOrSaleProvider(): array
    {
        return [
            'coupons' => ['coupons'],
            'sales' => ['sales'],
        ];
    }

    public function authWithDiscountProvider(): array
    {
        return [
            'as user coupons' => ['user', 'coupons'],
            'as user sales' => ['user', 'sales'],
            'as app coupons' => ['application', 'coupons'],
            'as app sales' => ['application', 'sales'],
        ];
    }

    /**
     * @dataProvider couponOrSaleProvider
     */
    public function testIndexUnauthorized($discountKind): void
    {
        $response = $this->getJson("/${discountKind}");
        $response->assertForbidden();
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testIndex($user, $discountKind): void
    {
        $this->$user->givePermissionTo("${discountKind}.show");

        $this
            ->actingAs($this->$user)
            ->getJson("/${discountKind}")
            ->assertOk()
            ->assertJsonCount(10, 'data');

        $this->assertQueryCountLessThan(15);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testIndexPerformance($user, $discountKind): void
    {
        $this->$user->givePermissionTo("${discountKind}.show");

        $codes = $discountKind === 'coupons' ? [] : ['code' => null];
        Discount::factory($codes)->count(490)->create();

        $this
            ->actingAs($this->$user)
            ->getJson("/${discountKind}?limit=500")
            ->assertOk()
            ->assertJsonCount(500, 'data');

        $this->assertQueryCountLessThan(15);
    }

    public function testShowUnauthorized(): void
    {
        $discount = Discount::factory()->create();

        $response = $this->getJson('/coupons/' . $discount->code);
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testShow($user): void
    {
        $this->$user->givePermissionTo('coupons.show_details');
        $discount = Discount::factory()->create();

        $response = $this->actingAs($this->$user)->getJson('/coupons/' . $discount->code);
        $response
            ->assertOk()
            ->assertJsonStructure($this->expectedStructure)
            ->assertJsonFragment(['id' => $discount->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowWithConditions($user): void
    {
        $this->$user->givePermissionTo('coupons.show_details');
        $discount = Discount::factory()->create();

        $conditionGroup = ConditionGroup::create();

        $condition = $conditionGroup->conditions()->create([
            'type' => ConditionType::USER_IN_ROLE,
            'value' => [
                'roles' => [
                    $this->role->getKey(),
                ],
                'is_allow_list' => true,
            ],
        ]);
        $condition->roles()->attach($this->role);

        $condition2 = $conditionGroup->conditions()->create([
            'type' => ConditionType::USER_IN,
            'value' => [
                'users' => [
                    $this->conditionUser->getKey(),
                ],
                'is_allow_list' => true,
            ],
        ]);

        $condition2->users()->attach($this->conditionUser);

        $condition3 = $conditionGroup->conditions()->create([
            'type' => ConditionType::PRODUCT_IN_SET,
            'value' => [
                'product_sets' => [
                    $this->conditionProductSet->getKey(),
                ],
                'is_allow_list' => true,
            ],
        ]);

        $condition3->productSets()->attach($this->conditionProductSet);

        $condition4 = $conditionGroup->conditions()->create([
            'type' => ConditionType::PRODUCT_IN,
            'value' => [
                'products' => [
                    $this->conditionProduct->getKey(),
                ],
                'is_allow_list' => true,
            ],
        ]);

        $condition4->products()->attach($this->conditionProduct);

        $discount->conditionGroups()->attach($conditionGroup);

        $response = $this->actingAs($this->$user)->getJson('/coupons/' . $discount->code);
        $response
            ->assertOk()
            ->assertJsonStructure($this->expectedStructure)
            ->assertJsonFragment([
                'id' => $discount->getKey(),
                'name' => $discount->name,
                'description' => $discount->description,
                'value' => $discount->value,
                'type' => $discount->type,
                'priority' => $discount->priority,
                'uses' => $discount->uses,
            ])
            ->assertJsonFragment([
                'type' => ConditionType::USER_IN_ROLE,
                'is_allow_list' => true,
            ])
            ->assertJsonFragment([
                'type' => ConditionType::USER_IN,
                'is_allow_list' => true,
            ])
            ->assertJsonFragment([
                'type' => ConditionType::PRODUCT_IN_SET,
                'is_allow_list' => true,
            ])
            ->assertJsonFragment([
                'type' => ConditionType::PRODUCT_IN,
                'is_allow_list' => true,
            ])
            ->assertJsonFragment([
                'id' => $this->role->getKey(),
                'name' => $this->role->name,
                'description' => $this->role->description,
            ])
            ->assertJsonFragment([
                'id' => $this->conditionUser->getKey(),
                'name' => $this->conditionUser->name,
                'email' => $this->conditionUser->email,
            ])
            ->assertJsonFragment([
                'id' => $this->conditionProductSet->getKey(),
                'name' => $this->conditionProductSet->name,
                'slug' => $this->conditionProductSet->slug,
            ])
            ->assertJsonFragment([
                'id' => $this->conditionProduct->getKey(),
                'name' => $this->conditionProduct->name,
                'slug' => $this->conditionProduct->slug,
            ]);
    }

    /**
     * @dataProvider couponOrSaleProvider
     */
    public function testShowByIdUnauthorized($discountKind): void
    {
        $code = $discountKind === 'coupons' ? [] : ['code' => null];

        $discount = Discount::factory($code)->create();

        $response = $this->getJson("/${discountKind}/id:" . $discount->getKey());
        $response->assertForbidden();
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testShowById($user, $discountKind): void
    {
        $this->$user->givePermissionTo("${discountKind}.show_details");

        $code = $discountKind === 'coupons' ? [] : ['code' => null];
        $discount = Discount::factory($code)->create();

        $this
            ->actingAs($this->$user)
            ->getJson("/${discountKind}/id:" . $discount->getKey())
            ->assertOk()
            ->assertJsonFragment(['id' => $discount->getKey()]);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testShowInvalidDiscount($user, $discountKind): void
    {
        $this->$user->givePermissionTo("${discountKind}.show_details");

        $code = $discountKind === 'sales' ? [] : ['code' => null];
        $discount = Discount::factory($code)->create();

        $this
            ->actingAs($this->$user)
            ->json('GET', "/${discountKind}/id:" .  $discount->getKey())
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowWrongCode($user): void
    {
        $this->$user->givePermissionTo('coupons.show_details');
        $discount = Discount::factory()->create();

        $this
            ->actingAs($this->$user)
            ->getJson('/coupons/its_not_code')
            ->assertNotFound();

        $this
            ->actingAs($this->$user)
            ->getJson('/coupons/' . $discount->code . '_' . $discount->code)
            ->assertNotFound();
    }

    /**
     * @dataProvider couponOrSaleProvider
     */
    public function testCreateUnauthorized($discountKind): void
    {
        Event::fake();

        $response = $this->postJson("/${discountKind}");
        $response->assertForbidden();

        $event = $discountKind === 'coupons' ? CouponCreated::class : SaleCreated::class;
        Event::assertNotDispatched($event);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testCreateSimple($user, $discountKind): void
    {
        $this->$user->givePermissionTo("${discountKind}.add");

        $event = $discountKind === 'coupons' ? CouponCreated::class : SaleCreated::class;

        Event::fake($event);

        $discount = [
            'name' => 'Kupon',
            'description' => 'Testowy kupon',
            'value' => 10,
            'type' => DiscountType::PERCENTAGE,
            'priority' => 1,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
        ];

        if ($discountKind === 'coupons') {
            $discount['code'] = 'S43SA2';
        }

        $conditions = [
            'condition_groups' => [
                [
                    'conditions' => [
                        [
                            'type' => ConditionType::MAX_USES,
                            'max_uses' => 150,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this
            ->actingAs($this->$user)
            ->json('POST', "/${discountKind}", $discount + $conditions);

        $response
            ->assertCreated()
            ->assertJsonFragment($discount)
            ->assertJsonFragment([
                'max_uses' => 150,
                'type' => ConditionType::MAX_USES,
            ]);

        $discountId = $response->getData()->data->id;

        $this->assertDatabaseHas('discounts', $discount + ['id' => $discountId]);
        $this->assertDatabaseCount('condition_groups', 1);
        $this->assertDatabaseHas('discount_condition_groups', ['discount_id' => $discountId]);
        $this->assertDatabaseCount('discount_conditions', 1);

        Event::assertDispatched($event);
        Queue::fake();

        $discount = Discount::find($response->getData()->data->id);
        $event = $discountKind === 'coupons' ? new CouponCreated($discount) : new SaleCreated($discount);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Queue::assertNotPushed(CallWebhookJob::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateSimpleWrongCode($user): void
    {
        $this->$user->givePermissionTo('coupons.add');

        $event = CouponCreated::class;

        Event::fake($event);

        $discount = [
            'name' => 'Kupon',
            'description' => 'Testowy kupon',
            'value' => 10,
            'type' => DiscountType::PERCENTAGE,
            'priority' => 1,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
            'code' => 'test as #',
        ];

        $conditions = [
            'condition_groups' => [
                [
                    'conditions' => [
                        [
                            'type' => ConditionType::MAX_USES,
                            'max_uses' => 150,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this
            ->actingAs($this->$user)
            ->json('POST', '/coupons', $discount + $conditions);

        $response
            ->assertStatus(422)
            ->assertJsonFragment(['key' => ValidationError::ALPHADASH]);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testCreateWithMetadata($user, $discountKind): void
    {
        $this->$user->givePermissionTo("${discountKind}.add");

        $event = $discountKind === 'coupons' ? CouponCreated::class : SaleCreated::class;

        Event::fake($event);

        $discount = [
            'name' => 'Kupon',
            'description' => 'Testowy kupon',
            'value' => 10,
            'type' => DiscountType::PERCENTAGE,
            'priority' => 1,
            'target_type' => DiscountTargetType::SHIPPING_PRICE,
            'target_is_allow_list' => true,
            'metadata' => [
                'attributeMeta' => 'attributeValue',
            ],
        ];

        if ($discountKind === 'coupons') {
            $discount['code'] = 'S43SA2';
        }

        $response = $this
            ->actingAs($this->$user)
            ->json('POST', "/${discountKind}", $discount);

        $response
            ->assertCreated()
            ->assertJsonFragment($discount);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testCreateWithMetadataPrivate($user, $discountKind): void
    {
        $this->$user->givePermissionTo(["${discountKind}.add", "${discountKind}.show_metadata_private"]);

        $event = $discountKind === 'coupons' ? CouponCreated::class : SaleCreated::class;

        Event::fake($event);

        $discount = [
            'name' => 'Kupon',
            'description' => 'Testowy kupon',
            'value' => 10,
            'type' => DiscountType::PERCENTAGE,
            'priority' => 1,
            'target_type' => DiscountTargetType::SHIPPING_PRICE,
            'target_is_allow_list' => true,
            'metadata_private' => [
                'attributeMetaPriv' => 'attributeValue',
            ],
        ];

        if ($discountKind === 'coupons') {
            $discount['code'] = 'S43SA2';
        }

        $response = $this
            ->actingAs($this->$user)
            ->json('POST', "/${discountKind}", $discount);

        $response
            ->assertCreated()
            ->assertJsonFragment($discount);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testCreateWithShippingMethod($user, $discountKind): void
    {
        $this->$user->givePermissionTo("${discountKind}.add");

        $event = $discountKind === 'coupons' ? CouponCreated::class : SaleCreated::class;

        Event::fake($event);

        $discount = [
            'name' => 'Kupon',
            'description' => 'Testowy kupon',
            'value' => 10,
            'type' => DiscountType::PERCENTAGE,
            'priority' => 1,
            'target_type' => DiscountTargetType::SHIPPING_PRICE,
            'target_is_allow_list' => true,
        ];

        if ($discountKind === 'coupons') {
            $discount['code'] = 'S43SA2';
        }

        $shippingMethod = ShippingMethod::factory()->create(['public' => true]);

        $shippingMethods = [
            'target_shipping_methods' => [
                $shippingMethod->getKey(),
            ],
        ];

        $response = $this
            ->actingAs($this->$user)
            ->json('POST', "/${discountKind}", $discount + $shippingMethods);

        $response
            ->assertCreated()
            ->assertJsonFragment($discount)
            ->assertJsonFragment([
                'id' => $shippingMethod->getKey(),
                'name' => $shippingMethod->name,
                'public' => true,
            ]);

        $discountId = $response->getData()->data->id;

        $this->assertDatabaseHas('discounts', $discount + ['id' => $discountId]);
        $this->assertDatabaseHas('model_has_discounts', [
            'discount_id' => $discountId,
            'model_type' => ShippingMethod::class,
            'model_id' => $shippingMethod->getKey(),
        ]);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testCreateWithProduct($user, $discountKind): void
    {
        $this->$user->givePermissionTo("${discountKind}.add");

        if ($discountKind === 'coupons') {
            $event = CouponCreated::class;
            $minPriceDiscounted = 900;
            $maxPriceDiscounted = 1200;
        } else {
            $event = SaleCreated::class;
            $minPriceDiscounted = 810;
            $maxPriceDiscounted = 1080;
        }

        Event::fake($event);

        $discount = [
            'name' => 'Kupon',
            'description' => 'Testowy kupon',
            'value' => 10,
            'type' => DiscountType::PERCENTAGE,
            'priority' => 1,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ];

        if ($discountKind === 'coupons') {
            $discount['code'] = 'S43SA2';
        }

        $product = Product::factory()->create([
            'public' => true,
            'price' => 1000,
            'price_min_initial' => 900,
            'price_max_initial' => 1200,
        ]);

        $productSet = ProductSet::factory()->create(['public' => true]);

        $data = [
            'target_products' => [
                $product->getKey(),
            ],
            'target_sets' => [
                $productSet->getKey(),
            ],
        ];

        $response = $this
            ->actingAs($this->$user)
            ->json('POST', "/${discountKind}", $discount + $data);

        $response
            ->assertCreated()
            ->assertJsonFragment($discount)
            ->assertJsonFragment([
                'id' => $product->getKey(),
                'name' => $product->name,
                'public' => true,
                'price_min' => $minPriceDiscounted,
                'price_max' => $maxPriceDiscounted,
            ])
            ->assertJsonFragment([
                'id' => $productSet->getKey(),
                'name' => $productSet->name,
                'public' => true,
            ]);

        $discountId = $response->getData()->data->id;

        $this->assertDatabaseHas('discounts', $discount + ['id' => $discountId]);
        $this->assertDatabaseHas('model_has_discounts', [
            'discount_id' => $discountId,
            'model_type' => Product::class,
            'model_id' => $product->getKey(),
        ]);
        $this->assertDatabaseHas('model_has_discounts', [
            'discount_id' => $discountId,
            'model_type' => ProductSet::class,
            'model_id' => $productSet->getKey(),
        ]);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testCreateNoDescription($user, $discountKind): void
    {
        $this->$user->givePermissionTo("${discountKind}.add");

        Queue::fake();

        $discount = [
            'name' => 'Kupon',
            'value' => 10,
            'type' => DiscountType::PERCENTAGE,
            'priority' => 1,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
        ];

        if ($discountKind === 'coupons') {
            $discount['code'] = 'S43SA2';
        }

        $response = $this->actingAs($this->$user)->json('POST', "/${discountKind}", $discount + ['description' => '']);

        $response
            ->assertCreated()
            ->assertJsonFragment($discount);

        $discountId = $response->getData()->data->id;

        $this->assertDatabaseHas('discounts', $discount + ['id' => $discountId]);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testCreateMaxValuePercentage($user, $discountKind): void
    {
        $this->$user->givePermissionTo("${discountKind}.add");

        Queue::fake();

        $discount = [
            'name' => 'Kupon',
            'value' => 855,
            'type' => DiscountType::PERCENTAGE,
            'priority' => 1,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
        ];

        if ($discountKind === 'coupons') {
            $discount['code'] = 'S43SA2';
        }

        $response = $this->actingAs($this->$user)->json('POST', "/${discountKind}", $discount);

        $response
            ->assertStatus(422);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testCreateMaxValueAmount($user, $discountKind): void
    {
        $this->$user->givePermissionTo("${discountKind}.add");

        Queue::fake();

        $discount = [
            'name' => 'Kupon',
            'value' => 855,
            'type' => DiscountType::AMOUNT,
            'priority' => 1,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
        ];

        if ($discountKind === 'coupons') {
            $discount['code'] = 'S43SA2';
        }

        $response = $this->actingAs($this->$user)->json('POST', "/${discountKind}", $discount);

        $response
            ->assertCreated()
            ->assertJsonFragment($discount);

        $discountId = $response->getData()->data->id;

        $this->assertDatabaseHas('discounts', $discount + ['id' => $discountId]);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testCreateMinValuePercentage($user, $discountKind): void
    {
        $this->$user->givePermissionTo("${discountKind}.add");

        Queue::fake();

        $discount = [
            'name' => 'Kupon',
            'value' => -10,
            'type' => DiscountType::PERCENTAGE,
            'priority' => 1,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
        ];

        if ($discountKind === 'coupons') {
            $discount['code'] = 'S43SA2';
        }

        $response = $this->actingAs($this->$user)->json('POST', "/${discountKind}", $discount);

        $response
            ->assertStatus(422);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testCreateMinValueAmount($user, $discountKind): void
    {
        $this->$user->givePermissionTo("${discountKind}.add");

        Queue::fake();

        $discount = [
            'name' => 'Kupon',
            'value' => -10,
            'type' => DiscountType::AMOUNT,
            'priority' => 1,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
        ];

        if ($discountKind === 'coupons') {
            $discount['code'] = 'S43SA2';
        }

        $response = $this->actingAs($this->$user)->json('POST', "/${discountKind}", $discount);

        $response
            ->assertStatus(422);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testCreateFull($user, $discountKind): void
    {
        $this->$user->givePermissionTo("${discountKind}.add");

        $discount = [
            'name' => 'Kupon',
            'description' => 'Testowy kupon',
            'value' => 10,
            'type' => DiscountType::PERCENTAGE,
            'priority' => 1,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
        ];

        if ($discountKind === 'coupons') {
            $discount['code'] = 'S43SA2';
        }

        $conditions = [
            'condition_groups' => [
                [
                    'conditions' => $this->conditions,
                ],
            ],
        ];

        $response = $this->actingAs($this->$user)->json('POST', "/${discountKind}", $discount + $conditions);

        $response
            ->assertCreated()
            ->assertJsonFragment($discount);

        foreach ($this->conditions as $condition) {
            if (
                !in_array(
                    $condition['type'],
                    [
                        ConditionType::USER_IN_ROLE,
                        ConditionType::USER_IN,
                        ConditionType::PRODUCT_IN,
                        ConditionType::PRODUCT_IN_SET,
                    ]
                )
            ) {
                $response->assertJsonFragment($condition);
            }
        }

        $response
            ->assertJsonFragment([
                'type' => ConditionType::USER_IN_ROLE,
                'is_allow_list' => true,
            ])
            ->assertJsonFragment([
                'id' => $this->role->getKey(),
                'name' => $this->role->name,
                'description' => $this->role->description,
            ])
            ->assertJsonFragment([
                'type' => ConditionType::USER_IN,
                'is_allow_list' => true,
            ])
            ->assertJsonFragment([
                'id' => $this->conditionUser->getKey(),
                'email' => $this->conditionUser->email,
                'name' => $this->conditionUser->name,
            ])
            ->assertJsonFragment([
                'type' => ConditionType::PRODUCT_IN,
                'is_allow_list' => true,
            ])
            ->assertJsonFragment([
                'id' => $this->conditionProduct->getKey(),
                'name' => $this->conditionProduct->name,
                'slug' => $this->conditionProduct->slug,
            ])
            ->assertJsonFragment([
                'type' => ConditionType::PRODUCT_IN_SET,
                'is_allow_list' => true,
            ])
            ->assertJsonFragment([
                'id' => $this->conditionProductSet->getKey(),
                'name' => $this->conditionProductSet->name,
                'slug' => $this->conditionProductSet->slug,
            ]);

        $discountId = $response->getData()->data->id;

        $this->assertDatabaseHas('discounts', $discount + ['id' => $discountId]);
        $this->assertDatabaseCount('condition_groups', 1);
        $this->assertDatabaseHas('discount_condition_groups', ['discount_id' => $discountId]);
        $this->assertDatabaseCount('discount_conditions', count($this->conditions));
    }

    /**
     * @dataProvider booleanProvider
     */
    public function testCreateBooleanValuesCoupon($user, $boolean, $booleanValue): void
    {
        $this->$user->givePermissionTo('coupons.add');

        $discount = [
            'code' => 'S43SA2',
            'name' => 'Kupon',
            'description' => 'Testowy kupon',
            'value' => 10,
            'type' => DiscountType::PERCENTAGE,
            'priority' => 1,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => $boolean,
        ];

        $conditions = [
            'condition_groups' => [
                [
                    'conditions' => [
                        [
                            'type' => ConditionType::ORDER_VALUE,
                            'min_value' => 100,
                            'max_value' => 500,
                            'include_taxes' => $boolean,
                            'is_in_range' => $boolean,
                        ],
                        [
                            'type' => ConditionType::USER_IN_ROLE,
                            'roles' => [
                                $this->role->getKey(),
                            ],
                            'is_allow_list' => $boolean,
                        ],
                        [
                            'type' => ConditionType::USER_IN,
                            'users' => [
                                $this->conditionUser->getKey(),
                            ],
                            'is_allow_list' => $boolean,
                        ],
                        [
                            'type' => ConditionType::PRODUCT_IN_SET,
                            'product_sets' => [
                                $this->conditionProductSet->getKey(),
                            ],
                            'is_allow_list' => $boolean,
                        ],
                        [
                            'type' => ConditionType::PRODUCT_IN,
                            'products' => [
                                $this->conditionProduct->getKey(),
                            ],
                            'is_allow_list' => $boolean,
                        ],
                        [
                            'type' => ConditionType::DATE_BETWEEN,
                            'start_at' => Carbon::now(),
                            'end_at' => Carbon::tomorrow(),
                            'is_in_range' => $boolean,
                        ],
                        [
                            'type' => ConditionType::TIME_BETWEEN,
                            'start_at' => Carbon::now()->toTimeString(),
                            'end_at' => Carbon::tomorrow()->toTimeString(),
                            'is_in_range' => $boolean,
                        ],
                        [
                            'type' => ConditionType::WEEKDAY_IN,
                            'weekday' => [$boolean, 'on', 'off', 'no', 1, 'yes', $boolean],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->$user)->json('POST', '/coupons', $discount + $conditions);

        $discountId = $response->getData()->data->id;

        $discountResponse = array_merge($discount, [
            'id' => $discountId,
            'target_is_allow_list' => $booleanValue,
        ]);

        $response
            ->assertCreated()
            ->assertJsonFragment($discountResponse);

        $response
            ->assertJsonFragment([
                'type' => ConditionType::USER_IN_ROLE,
                'is_allow_list' => $booleanValue,
            ])
            ->assertJsonFragment([
                'type' => ConditionType::USER_IN,
                'is_allow_list' => $booleanValue,
            ])
            ->assertJsonFragment([
                'type' => ConditionType::PRODUCT_IN,
                'is_allow_list' => $booleanValue,
            ])
            ->assertJsonFragment([
                'type' => ConditionType::PRODUCT_IN_SET,
                'is_allow_list' => $booleanValue,
            ])
            ->assertJsonFragment([
                'type' => ConditionType::ORDER_VALUE,
                'min_value' => 100,
                'max_value' => 500,
                'include_taxes' => $booleanValue,
                'is_in_range' => $booleanValue,
            ])
            ->assertJsonFragment([
                'type' => ConditionType::DATE_BETWEEN,
                'is_in_range' => $booleanValue,
            ])
            ->assertJsonFragment([
                'type' => ConditionType::TIME_BETWEEN,
                'is_in_range' => $booleanValue,
            ])
            ->assertJsonFragment([
                'type' => ConditionType::WEEKDAY_IN,
                'weekday' => [$booleanValue, true, false, false, true, true, $booleanValue],
            ]);

        $this->assertDatabaseHas('discounts', $discountResponse);
        $this->assertDatabaseCount('condition_groups', 1);
        $this->assertDatabaseHas('discount_condition_groups', ['discount_id' => $discountId]);
        $this->assertDatabaseCount('discount_conditions', 8);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testCreateWeekdayInCondition($user, $discountKind): void
    {
        $this->$user->givePermissionTo("${discountKind}.add");

        $discount = [
            'name' => 'Kupon',
            'description' => 'Testowy kupon',
            'value' => 10,
            'type' => DiscountType::PERCENTAGE,
            'priority' => 1,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
        ];

        if ($discountKind === 'coupons') {
            $discount['code'] = 'S43SA2';
        }

        $conditions = [
            'condition_groups' => [
                [
                    'conditions' => [
                        [
                            'type' => ConditionType::WEEKDAY_IN,
                            'weekday' => [false, true, false, false, true, true, false],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->$user)->json('POST', "/${discountKind}", $discount + $conditions);

        $response
            ->assertCreated()
            ->assertJsonFragment($discount)
            ->assertJsonFragment([
                'type' => ConditionType::WEEKDAY_IN,
                'weekday' => [false, true, false, false, true, true, false],
            ]);

        $discountModel = Discount::find($response->getData()->data->id);
        $conditionGroup = $discountModel->conditionGroups->first();

        $this->assertDatabaseHas('discounts', $discount + ['id' => $discountModel->getKey()]);
        $this->assertDatabaseCount('condition_groups', 1);
        $this->assertDatabaseHas('discount_condition_groups', ['discount_id' => $discountModel->getKey()]);
        $this->assertDatabaseCount('discount_conditions', 1);

        $this->assertDatabaseHas('discount_conditions', [
            'condition_group_id' => $conditionGroup->getKey(),
            'type' => ConditionType::WEEKDAY_IN,
        ]);

        // Checking discount_conditions value in DB
        $condition = DB::table('discount_conditions')
            ->where('condition_group_id', $conditionGroup->getKey())
            ->select('value')
            ->first();
        $this->assertTrue(json_decode($condition->value)->weekday === 38); // DEC(38) == BIN(0100110)
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testCreateDateBetweenCondition($user, $discountKind): void
    {
        $this->$user->givePermissionTo("${discountKind}.add");

        $discount = [
            'name' => 'Kupon',
            'description' => 'Testowy kupon',
            'value' => 10,
            'type' => DiscountType::PERCENTAGE,
            'priority' => 1,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
        ];

        if ($discountKind === 'coupons') {
            $discount['code'] = 'S43SA2';
        }

        $conditions = [
            'condition_groups' => [
                [
                    'conditions' => [
                        [
                            'type' => ConditionType::DATE_BETWEEN,
                            'is_in_range' => true,
                            'start_at' => '2022-04-15',
                            'end_at' => '2022-04-20',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->$user)->json('POST', "/${discountKind}", $discount + $conditions);

        $response
            ->assertCreated()
            ->assertJsonFragment($discount)
            ->assertJsonFragment([
                'type' => ConditionType::DATE_BETWEEN,
                'is_in_range' => true,
                'start_at' => '2022-04-15',
                'end_at' => '2022-04-20',
            ]);

        $discountModel = Discount::find($response->getData()->data->id);
        $conditionGroup = $discountModel->conditionGroups->first();

        $this->assertDatabaseHas('discounts', $discount + ['id' => $discountModel->getKey()]);
        $this->assertDatabaseCount('condition_groups', 1);
        $this->assertDatabaseHas('discount_condition_groups', ['discount_id' => $discountModel->getKey()]);
        $this->assertDatabaseCount('discount_conditions', 1);

        $this->assertDatabaseHas('discount_conditions', [
            'condition_group_id' => $conditionGroup->getKey(),
            'type' => ConditionType::DATE_BETWEEN,
        ]);
    }

    public function timeConditionProvider(): array
    {
        return [
            'as user date between' => [
                'user',
                [
                    'type' => ConditionType::DATE_BETWEEN,
                    'is_in_range' => true,
                    'start_at' => '2022-05-09',
                    'end_at' => '2022-05-13',
                ],
            ],
            'as user time between' => [
                'user',
                [
                    'type' => ConditionType::TIME_BETWEEN,
                    'is_in_range' => true,
                    'start_at' => '10:00:00',
                    'end_at' => '14:00:00',
                ],
            ],
            'as user weekday in' => [
                'user',
                [
                    'type' => ConditionType::WEEKDAY_IN,
                    'weekday' => [0, 0, 0, 0, 1, 0, 0],
                ],
            ],
            'as app date between' => [
                'application',
                [
                    'type' => ConditionType::DATE_BETWEEN,
                    'is_in_range' => true,
                    'start_at' => '2022-05-09',
                    'end_at' => '2022-05-13',
                ],
            ],
            'as app time between' => [
                'application',
                [
                    'type' => ConditionType::TIME_BETWEEN,
                    'is_in_range' => true,
                    'start_at' => '10:00:00',
                    'end_at' => '14:00:00',
                ],
            ],
            'as app weekday in' => [
                'application',
                [
                    'type' => ConditionType::WEEKDAY_IN,
                    'weekday' => [0, 0, 0, 0, 1, 0, 0],
                ],
            ],
        ];
    }

    /**
     * @dataProvider timeConditionProvider
     */
    public function testCreateSaleAddToCache($user, $condition): void
    {
        Carbon::setTestNow('2022-05-12T12:00:00'); // Thursday
        $this->$user->givePermissionTo('sales.add');

        $discount = [
            'name' => 'Sale',
            'description' => 'Test sale',
            'value' => 10,
            'type' => DiscountType::PERCENTAGE,
            'priority' => 1,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
            'code' => null,
        ];

        $conditions = [
            'condition_groups' => [
                [
                    'conditions' => [
                        $condition,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->$user)->json('POST', 'sales', $discount + $conditions);

        $response->assertCreated();

        $discountModel = Discount::find($response->getData()->data->id);

        $activeSales = Cache::get('sales.active');
        $this->assertCount(1, $activeSales);
        $this->assertTrue($activeSales->contains($discountModel->getKey()));
    }

    /**
     * @dataProvider timeConditionProvider
     */
    public function testCreateSaleNoAddToCache($user, $condition): void
    {
        Carbon::setTestNow('2022-05-20T16:00:00'); // Friday
        $this->$user->givePermissionTo('sales.add');

        $discount = [
            'name' => 'Sale',
            'description' => 'Test sale',
            'value' => 10,
            'type' => DiscountType::PERCENTAGE,
            'priority' => 1,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
            'code' => null,
        ];

        $conditions = [
            'condition_groups' => [
                [
                    'conditions' => [
                        $condition,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->$user)->json('POST', 'sales', $discount + $conditions);

        $response->assertCreated();

        $discountModel = Discount::find($response->getData()->data->id);

        $activeSales = Cache::get('sales.active');
        $this->assertCount(0, $activeSales);
        $this->assertFalse($activeSales->contains($discountModel->getKey()));
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testCreateInvalidConditionType($user, $discountKind): void
    {
        $this->$user->givePermissionTo("${discountKind}.add");

        $discount = [
            'name' => 'Kupon',
            'description' => 'Testowy kupon',
            'value' => 10,
            'type' => DiscountType::PERCENTAGE,
            'priority' => 1,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
            'condition_groups' => [
                [
                    'conditions' => [
                        [
                            'type' => 'invalid-condition-type',
                            'weekday' => [false, true, false, false, true, true, false],
                        ],
                    ],
                ],
            ],
        ];

        if ($discountKind === 'coupons') {
            $discount['code'] = 'S43SA2';
        }

        $response = $this->actingAs($this->$user)->json('POST', "/${discountKind}", $discount);

        $response
            ->assertStatus(422);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testCreateWithWebHookEvent($user, $discountKind): void
    {
        $this->$user->givePermissionTo("${discountKind}.add");

        if ($discountKind === 'coupons') {
            $event = CouponCreated::class;
            $webHookEvent = 'CouponCreated';
            $code = ['code' => 'S43SA2'];
        } else {
            $event = SaleCreated::class;
            $webHookEvent = 'SaleCreated';
            $code = [];
        }

        $webHook = WebHook::factory()->create([
            'events' => [
                $webHookEvent,
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Event::fake($event);

        $response = $this->actingAs($this->$user)->json('POST', "/${discountKind}", [
            'name' => 'Kupon',
            'description' => 'Testowy kupon',
            'value' => 10,
            'type' => DiscountType::PERCENTAGE,
            'priority' => 1,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
        ] + $code);

        Event::assertDispatched($event);

        $discount = Discount::find($response->getData()->data->id);
        $event = $discountKind === 'coupons' ? new CouponCreated($discount) : new SaleCreated($discount);

        Bus::fake();

        $listener = new WebHookEventListener();

        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $discount, $webHookEvent) {
            $payload = $job->payload;
            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $discount->getKey()
                && $payload['data_type'] === 'Discount'
                && $payload['event'] === $webHookEvent;
        });
    }

    /**
     * @dataProvider couponOrSaleProvider
     */
    public function testUpdateUnauthorized($discountKind): void
    {
        $code = $discountKind === 'coupons' ? [] : ['code' => null];
        $discount = Discount::factory($code)->create();

        Event::fake();

        $this
            ->patchJson("/${discountKind}/id:" .  $discount->getKey())
            ->assertForbidden();

        $event = $discountKind === 'coupons' ? CouponUpdated::class : SaleUpdated::class;
        Event::assertNotDispatched($event);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testUpdateInvalidDiscount($user, $discountKind): void
    {
        $this->$user->givePermissionTo("${discountKind}.edit");

        $code = $discountKind === 'sales' ? [] : ['code' => null];
        $discount = Discount::factory($code)->create();

        Event::fake();

        $this
            ->actingAs($this->$user)
            ->patchJson("/${discountKind}/id:" .  $discount->getKey(), [
                'code' => 'S43SA2',
            ])
            ->assertNotFound();

        $event = $discountKind === 'coupons' ? CouponCreated::class : SaleCreated::class;
        Event::assertNotDispatched($event);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testUpdateEmptyName($user, $discountKind): void
    {
        $this->$user->givePermissionTo("${discountKind}.edit");

        $code = $discountKind === 'sales' ? ['code' => null] : [];
        $discount = Discount::factory($code)->create();

        $this
            ->actingAs($this->$user)
            ->patchJson("/${discountKind}/id:" .  $discount->getKey(), [
                'name' => '',
            ])
            ->assertUnprocessable();
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testUpdateFull($user, $discountKind): void
    {
        $this->$user->givePermissionTo("${discountKind}.edit");
        $code = $discountKind === 'coupons' ? [] : ['code' => null];
        $discount = Discount::factory(['target_type' => DiscountTargetType::ORDER_VALUE] + $code)->create();

        $conditionGroup = ConditionGroup::create();
        $discountCondition = $conditionGroup->conditions()->create(
            [
                'type' => ConditionType::MAX_USES,
                'value' => ['max_uses' => 1000],
            ]
        );

        $discount->conditionGroups()->attach($conditionGroup);

        $discountNew = [
            'name' => 'Kupon',
            'description' => 'Testowy kupon',
            'value' => 10,
            'type' => DiscountType::PERCENTAGE,
            'priority' => 1,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ];

        if ($discountKind === 'coupons') {
            $discountNew['code'] = 'S43SA2';
        }

        $conditions = [
            'condition_groups' => [
                [
                    'conditions' => $this->conditions,
                ],
            ],
        ];

        Queue::fake();

        $response = $this->actingAs($this->$user)
            ->json('PATCH', "/${discountKind}/id:" . $discount->getKey(), $discountNew + $conditions);

        $response
            ->assertOk()
            ->assertJsonFragment($discountNew + ['id' => $discount->getKey()])
            ->assertJsonMissing($discountCondition->value);

        foreach ($this->conditions as $condition) {
            if (
                !in_array(
                    $condition['type'],
                    [
                        ConditionType::USER_IN_ROLE,
                        ConditionType::USER_IN,
                        ConditionType::PRODUCT_IN,
                        ConditionType::PRODUCT_IN_SET,
                    ]
                )
            ) {
                $response->assertJsonFragment($condition);
            }
        }

        $response
            ->assertJsonFragment([
                'type' => ConditionType::USER_IN,
                'is_allow_list' => true,
            ])
            ->assertJsonFragment([
                'id' => $this->conditionUser->getKey(),
                'email' => $this->conditionUser->email,
                'name' => $this->conditionUser->name,
            ])
            ->assertJsonFragment([
                'type' => ConditionType::USER_IN_ROLE,
                'is_allow_list' => true,
            ])
            ->assertJsonFragment([
                'id' => $this->role->getKey(),
                'name' => $this->role->name,
                'description' => $this->role->description,
            ])
            ->assertJsonFragment([
                'type' => ConditionType::PRODUCT_IN,
                'is_allow_list' => true,
            ])
            ->assertJsonFragment([
                'id' => $this->conditionProduct->getKey(),
                'name' => $this->conditionProduct->name,
                'slug' => $this->conditionProduct->slug,
            ])
            ->assertJsonFragment([
                'type' => ConditionType::PRODUCT_IN_SET,
                'is_allow_list' => true,
            ])
            ->assertJsonFragment([
                'id' => $this->conditionProductSet->getKey(),
                'name' => $this->conditionProductSet->name,
                'slug' => $this->conditionProductSet->slug,
            ]);

        $this->assertDatabaseHas('discounts', $discountNew + ['id' => $discount->getKey()]);
        $this->assertDatabaseCount('condition_groups', 1);
        $this->assertDatabaseCount('discount_condition_groups', 1);
        $this->assertDatabaseHas('discount_condition_groups', ['discount_id' => $discount->getKey()]);
        $this->assertDatabaseCount('discount_conditions', count($this->conditions));

        Queue::assertPushed(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class;
        });

        $discount = Discount::find($discount->getKey());
        $event = $discountKind === 'coupons' ? new CouponCreated($discount) : new SaleCreated($discount);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Queue::assertNotPushed(CallWebhookJob::class);
    }

    /**
     * @dataProvider booleanProvider
     */
    public function testUpdateBooleanValuesCoupon($user, $boolean, $booleanValue): void
    {
        $this->$user->givePermissionTo('coupons.edit');
        $discount = Discount::factory(['target_type' => DiscountTargetType::ORDER_VALUE])->create();

        $discountNew = [
            'code' => 'S43SA2',
            'name' => 'Kupon',
            'description' => 'Testowy kupon',
            'value' => 10,
            'type' => DiscountType::PERCENTAGE,
            'priority' => 1,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => $boolean,
        ];

        $conditions = [
            'condition_groups' => [
                [
                    'conditions' => [
                        [
                            'type' => ConditionType::ORDER_VALUE,
                            'min_value' => 100,
                            'max_value' => 500,
                            'include_taxes' => $boolean,
                            'is_in_range' => $boolean,
                        ],
                        [
                            'type' => ConditionType::USER_IN_ROLE,
                            'roles' => [
                                $this->role->getKey(),
                            ],
                            'is_allow_list' => $boolean,
                        ],
                        [
                            'type' => ConditionType::USER_IN,
                            'users' => [
                                $this->conditionUser->getKey(),
                            ],
                            'is_allow_list' => $boolean,
                        ],
                        [
                            'type' => ConditionType::PRODUCT_IN_SET,
                            'product_sets' => [
                                $this->conditionProductSet->getKey(),
                            ],
                            'is_allow_list' => $boolean,
                        ],
                        [
                            'type' => ConditionType::PRODUCT_IN,
                            'products' => [
                                $this->conditionProduct->getKey(),
                            ],
                            'is_allow_list' => $boolean,
                        ],
                        [
                            'type' => ConditionType::DATE_BETWEEN,
                            'start_at' => Carbon::now(),
                            'end_at' => Carbon::tomorrow(),
                            'is_in_range' => $boolean,
                        ],
                        [
                            'type' => ConditionType::TIME_BETWEEN,
                            'start_at' => Carbon::now()->toTimeString(),
                            'end_at' => Carbon::tomorrow()->toTimeString(),
                            'is_in_range' => $boolean,
                        ],
                        [
                            'type' => ConditionType::WEEKDAY_IN,
                            'weekday' => [$boolean, 'on', 'off', 'no', 1, 'yes', $boolean],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this
            ->actingAs($this->$user)
            ->json('PATCH', '/coupons/id:' . $discount->getKey(), $discountNew + $conditions);

        $discountResponse = array_merge($discountNew, [
            'id' => $discount->getKey(),
            'target_is_allow_list' => $booleanValue,
        ]);

        $response
            ->assertOk()
            ->assertJsonFragment($discountResponse);

        $response
            ->assertJsonFragment([
                'type' => ConditionType::USER_IN_ROLE,
                'is_allow_list' => $booleanValue,
            ])
            ->assertJsonFragment([
                'type' => ConditionType::USER_IN,
                'is_allow_list' => $booleanValue,
            ])
            ->assertJsonFragment([
                'type' => ConditionType::PRODUCT_IN,
                'is_allow_list' => $booleanValue,
            ])
            ->assertJsonFragment([
                'type' => ConditionType::PRODUCT_IN_SET,
                'is_allow_list' => $booleanValue,
            ])
            ->assertJsonFragment([
                'type' => ConditionType::ORDER_VALUE,
                'min_value' => 100,
                'max_value' => 500,
                'include_taxes' => $booleanValue,
                'is_in_range' => $booleanValue,
            ])
            ->assertJsonFragment([
                'type' => ConditionType::DATE_BETWEEN,
                'is_in_range' => $booleanValue,
            ])
            ->assertJsonFragment([
                'type' => ConditionType::TIME_BETWEEN,
                'is_in_range' => $booleanValue,
            ])
            ->assertJsonFragment([
                'type' => ConditionType::WEEKDAY_IN,
                'weekday' => [$booleanValue, true, false, false, true, true, $booleanValue],
            ]);

        $this->assertDatabaseHas('discounts', $discountResponse);
        $this->assertDatabaseHas('discount_condition_groups', ['discount_id' => $discount->getKey()]);
        $this->assertDatabaseCount('discount_conditions', 8);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testUpdateWithPartialData($user, $discountKind): void
    {
        $this->$user->givePermissionTo("${discountKind}.edit");
        $code = $discountKind === 'coupons' ? [] : ['code' => null];

        $discount = Discount::factory()->create($code);

        Queue::fake();

        $response = $this->actingAs($this->$user)
            ->json('PATCH', "/${discountKind}/id:" . $discount->getKey(), [
                'value' => 50,
            ]);

        $code = $discountKind === 'coupons' ? ['code' => $discount->code] : [];

        $response
            ->assertOk()
            ->assertJsonFragment([
                'id' => $discount->getKey(),
                'description' => $discount->description,
                'value' => 50,
                'type' => $discount->type,
                'metadata' => [],
            ] + $code);

        $this->assertDatabaseHas('discounts', [
            'id' => $discount->getKey(),
            'description' => $discount->description,
            'value' => 50,
            'type' => $discount->type,
        ] + $code);

        Queue::assertNotPushed(CallWebhookJob::class);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testUpdateWithWebHookQueue($user, $discountKind): void
    {
        $this->$user->givePermissionTo("${discountKind}.edit");

        if ($discountKind === 'coupons') {
            $webHookEvent = 'CouponUpdated';
            $code = [];
        } else {
            $webHookEvent = 'SaleUpdated';
            $code = ['code' => null];
        }
        $discount = Discount::factory($code)->create();

        $webHook = WebHook::factory()->create([
            'events' => [
                $webHookEvent,
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Queue::fake();

        $this->actingAs($this->$user)
            ->json('PATCH', "/${discountKind}/id:" . $discount->getKey(), [
                'description' => 'Weekend Sale',
                'discount' => 20,
                'type' => DiscountType::AMOUNT,
                'code' => $discount->code,
            ])->assertOk();

        Queue::assertPushed(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class;
        });

        $discount = Discount::find($discount->getKey());
        $event = $discountKind === 'coupons' ? new CouponUpdated($discount) : new SaleUpdated($discount);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Queue::assertPushed(CallWebhookJob::class, function ($job) use ($webHook, $discount, $webHookEvent) {
            $payload = $job->payload;
            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $discount->getKey()
                && $payload['data_type'] === 'Discount'
                && $payload['event'] === $webHookEvent;
        });
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testUpdateWithWebHookDispatched($user, $discountKind): void
    {
        $this->$user->givePermissionTo("${discountKind}.edit");

        if ($discountKind === 'coupons') {
            $webHookEvent = 'CouponUpdated';
            $code = [];
        } else {
            $webHookEvent = 'SaleUpdated';
            $code = ['code' => null];
        }
        $discount = Discount::factory($code)->create();

        $webHook = WebHook::factory()->create([
            'events' => [
                $webHookEvent,
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Bus::fake();

        $this->actingAs($this->$user)
            ->json('PATCH', "/${discountKind}/id:" . $discount->getKey(), [
                'description' => 'Weekend Sale',
                'discount' => 20,
                'type' => DiscountType::AMOUNT,
                'code' => $discount->code,
            ])->assertOk();

        Bus::assertDispatched(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class;
        });

        $discount = Discount::find($discount->getKey());
        $event = $discountKind === 'coupons' ? new CouponUpdated($discount) : new SaleUpdated($discount);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $discount, $webHookEvent) {
            $payload = $job->payload;
            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $discount->getKey()
                && $payload['data_type'] === 'Discount'
                && $payload['event'] === $webHookEvent;
        });
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateSaleWithProduct($user): void
    {
        $this->$user->givePermissionTo('sales.edit');
        $discount = Discount::factory(['target_type' => DiscountTargetType::PRODUCTS, 'code' => null])->create();

        $product1 = Product::factory()->create([
            'public' => true,
            'price' => 100,
            'price_min_initial' => 100,
            'price_max_initial' => 150,
        ]);

        $product2 = Product::factory()->create([
            'public' => true,
            'price' => 200,
            'price_min_initial' => 190,
            'price_max_initial' => 250,
        ]);

        $product3 = Product::factory()->create([
            'public' => true,
            'price' => 300,
            'price_min_initial' => 290,
            'price_max_initial' => 350,
        ]);

        $discount->products()->sync([$product1->getKey(), $product2->getKey()]);

        /** @var DiscountServiceContract $discountService */
        $discountService = App::make(DiscountServiceContract::class);

        // Apply discount to products before update
        $discountService->applyDiscountsOnProducts(Collection::make([$product1, $product2, $product3]));

        $discountNew = [
            'name' => 'Kupon',
            'description' => 'Testowy kupon',
            'value' => 10,
            'type' => DiscountType::AMOUNT,
            'priority' => 1,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ];

        $targetProducts = [
            'target_products' => [
                $product2->getKey(),
                $product3->getKey(),
            ],
        ];

        $response = $this->actingAs($this->$user)
            ->json('PATCH', '/sales/id:' . $discount->getKey(), $discountNew + $targetProducts);

        $response
            ->assertOk()
            ->assertJsonFragment($discountNew + ['id' => $discount->getKey()])
            ->assertJsonFragment([
                'id' => $product2->getKey(),
                'price' => 200,
                'price_min_initial' => 190,
                'price_max_initial' => 250,
                'price_min' => 180,
                'price_max' => 240,
            ])
            ->assertJsonFragment([
                'id' => $product3->getKey(),
                'price' => 300,
                'price_min_initial' => 290,
                'price_max_initial' => 350,
                'price_min' => 280,
                'price_max' => 340,
            ]);

        $this->assertDatabaseHas('discounts', $discountNew + ['id' => $discount->getKey()]);

        $this->assertDatabaseMissing('product_sales', [
            'product_id' => $product1->getKey(),
            'sale_id' => $discount->getKey(),
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product1->getKey(),
            'price' => 100,
            'price_min_initial' => 100,
            'price_max_initial' => 150,
            'price_min' => 100,
            'price_max' => 150,
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $product2->getKey(),
            'price' => 200,
            'price_min_initial' => 190,
            'price_max_initial' => 250,
            'price_min' => 180,
            'price_max' => 240,
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $product3->getKey(),
            'price' => 300,
            'price_min_initial' => 290,
            'price_max_initial' => 350,
            'price_min' => 280,
            'price_max' => 340,
        ]);
    }

    /**
     * @dataProvider couponOrSaleProvider
     */
    public function testDeleteUnauthorized($discountKind): void
    {
        $code = $discountKind === 'coupons' ? [] : ['code' => null];
        $discount = Discount::factory($code)->create();

        Event::fake();

        $this
            ->deleteJson("/${discountKind}/id:" . $discount->getKey())
            ->assertForbidden();

        $event = $discountKind === 'coupons' ? CouponDeleted::class : SaleDeleted::class;
        Event::assertNotDispatched($event);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testDeleteInvalidDiscount($user, $discountKind): void
    {
        $this->$user->givePermissionTo("${discountKind}.remove");

        $code = $discountKind === 'sales' ? [] : ['code' => null];
        $discount = Discount::factory($code)->create();

        Event::fake();

        $this
            ->actingAs($this->$user)
            ->deleteJson("/${discountKind}/id:" . $discount->getKey())
            ->assertNotFound();

        $event = $discountKind === 'coupons' ? CouponDeleted::class : SaleDeleted::class;
        Event::assertNotDispatched($event);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testDelete($user, $discountKind): void
    {
        $this->$user->givePermissionTo("${discountKind}.remove");
        $code = $discountKind === 'coupons' ? [] : ['code' => null];
        $discount = Discount::factory($code)->create();

        Queue::fake();

        $response = $this->actingAs($this->$user)->deleteJson("/${discountKind}/id:" . $discount->getKey());
        $response->assertNoContent();
        $this->assertSoftDeleted($discount);

        Queue::assertPushed(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class;
        });

        $event = $discountKind === 'coupons' ? new CouponDeleted($discount) : new SaleDeleted($discount);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Queue::assertNotPushed(CallWebhookJob::class);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testDeleteWithWebHookQueue($user, $discountKind): void
    {
        $this->$user->givePermissionTo("${discountKind}.remove");

        if ($discountKind === 'coupons') {
            $webHookEvent = 'CouponDeleted';
            $code = [];
        } else {
            $webHookEvent = 'SaleDeleted';
            $code = ['code' => null];
        }

        $discount = Discount::factory($code)->create();

        $webHook = WebHook::factory()->create([
            'events' => [
                $webHookEvent,
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Queue::fake();

        $response = $this->actingAs($this->$user)->deleteJson("/${discountKind}/id:" . $discount->getKey());

        Queue::assertPushed(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class;
        });

        $response->assertNoContent();
        $this->assertSoftDeleted($discount);

        $event = $discountKind === 'coupons' ? new CouponDeleted($discount) : new SaleDeleted($discount);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Queue::assertPushed(CallWebhookJob::class, function ($job) use ($webHook, $discount, $webHookEvent) {
            $payload = $job->payload;
            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $discount->getKey()
                && $payload['data_type'] === 'Discount'
                && $payload['event'] === $webHookEvent;
        });
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testDeleteWithWebHookDispatched($user, $discountKind): void
    {
        $this->$user->givePermissionTo("${discountKind}.remove");

        if ($discountKind === 'coupons') {
            $webHookEvent = 'CouponDeleted';
            $code = [];
        } else {
            $webHookEvent = 'SaleDeleted';
            $code = ['code' => null];
        }

        $discount = Discount::factory($code)->create();

        $webHook = WebHook::factory()->create([
            'events' => [
                $webHookEvent,
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Bus::fake();

        $response = $this->actingAs($this->$user)->deleteJson("/${discountKind}/id:" . $discount->getKey());

        Bus::assertDispatched(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class;
        });

        $response->assertNoContent();
        $this->assertSoftDeleted($discount);

        $event = $discountKind === 'coupons' ? new CouponDeleted($discount) : new SaleDeleted($discount);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $discount, $webHookEvent) {
            $payload = $job->payload;
            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $discount->getKey()
                && $payload['data_type'] === 'Discount'
                && $payload['event'] === $webHookEvent;
        });
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteSaleWithProduct($user): void
    {
        $this->$user->givePermissionTo('sales.remove');
        $discount = Discount::factory([
            'type' => DiscountType::AMOUNT,
            'value' => 10,
            'code' => null,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ])->create();

        $product = Product::factory()->create([
            'public' => true,
            'price' => 100,
            'price_min_initial' => 100,
            'price_max_initial' => 200,
        ]);

        $discount->products()->attach($product);

        /** @var DiscountServiceContract $discountService */
        $discountService = App::make(DiscountServiceContract::class);

        // Apply discount to products before update
        $discountService->applyDiscountsOnProducts(Collection::make([$product]));

        $this->assertDatabaseHas('products', [
            'id' => $product->getKey(),
            'price_min' => 90,
            'price_max' => 190,
        ]);

        $response = $this->actingAs($this->$user)->deleteJson('/sales/id:' . $discount->getKey());
        $response->assertNoContent();
        $this->assertSoftDeleted($discount);

        $this->assertDatabaseHas('products', [
            'id' => $product->getKey(),
            'price_min' => 100,
            'price_max' => 200,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateActiveSaleAndExpiredAfter($user): void
    {
        Carbon::setTestNow('2022-05-12T12:00:00'); // Thursday
        $this->$user->givePermissionTo('sales.add');

        $product = Product::factory()->create([
            'public' => true,
            'price' => 1000,
            'price_min_initial' => 1000,
            'price_max_initial' => 1000,
        ]);

        $discount = [
            'name' => 'Sale',
            'description' => 'Test sale',
            'value' => 10,
            'type' => DiscountType::PERCENTAGE,
            'priority' => 1,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
            'code' => null,
            'target_products' => [
                $product->getKey(),
            ],
        ];

        $conditions = [
            'condition_groups' => [
                [
                    'conditions' => [
                        [
                            'type' => ConditionType::TIME_BETWEEN,
                            'is_in_range' => true,
                            'start_at' => '10:00:00',
                            'end_at' => '14:00:00',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->$user)->json('POST', 'sales', $discount + $conditions);

        $response->assertCreated();

        $this->assertDatabaseHas('products', [
            'id' => $product->getKey(),
            'price_min' => 900,
            'price_max' => 900,
        ]);

        $discountModel = Discount::find($response->getData()->data->id);

        $activeSales = Cache::get('sales.active');
        $this->assertCount(1, $activeSales);
        $this->assertTrue($activeSales->contains($discountModel->getKey()));

        Carbon::setTestNow('2022-05-12T19:00:00');
        $this->travelTo('2022-05-12T19:00:00');
        $this->artisan('schedule:run');

        $this->assertDatabaseHas('products', [
            'id' => $product->getKey(),
            'price_min' => 1000,
            'price_max' => 1000,
        ]);

        $activeSales = Cache::get('sales.active');
        $this->assertCount(0, $activeSales);
        $this->assertFalse($activeSales->contains($discountModel->getKey()));
    }
}
