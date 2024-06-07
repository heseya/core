<?php

namespace Tests\Feature\Discounts;

use App\Enums\ConditionType;
use App\Enums\DiscountTargetType;
use App\Enums\DiscountType;
use App\Enums\ValidationError;
use App\Events\CouponCreated;
use App\Events\CouponUpdated;
use App\Events\ProductPriceUpdated;
use App\Events\SaleCreated;
use App\Events\SaleUpdated;
use App\Listeners\WebHookEventListener;
use App\Models\ConditionGroup;
use App\Models\Discount;
use App\Models\DiscountCondition;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Models\WebHook;
use App\Repositories\Contracts\ProductRepositoryContract;
use App\Services\Contracts\DiscountServiceContract;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\Price\Dtos\PriceDto;
use Domain\Price\Enums\DiscountConditionPriceType;
use Domain\Price\Enums\ProductPriceType;
use Domain\ProductSet\ProductSet;
use Domain\ShippingMethod\Models\ShippingMethod;
use Heseya\Dto\DtoException;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
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
    private array $minValues;
    private array $maxValues;
    private Role $role;
    private User $conditionUser;
    private Product $conditionProduct;
    private ProductSet $conditionProductSet;
    private array $expectedStructure;
    private ProductRepositoryContract $productRepository;
    private Currency $currency;

    public static function timeConditionProvider(): array
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

    public function setUp(): void
    {
        parent::setUp();

        $this->productRepository = App::make(ProductRepositoryContract::class);
        $this->currency = Currency::DEFAULT;

        // coupons
        Discount::factory()->count(10)->create();
        // sales
        Discount::factory([
            'code' => null,
            'target_type' => DiscountTargetType::ORDER_VALUE,
        ])->count(10)->create();

        $this->role = Role::factory()->create();
        $this->conditionUser = User::factory()->create();
        $this->conditionProduct = Product::factory()->create();
        $this->conditionProductSet = ProductSet::factory()->create();

        $this->minValues = [];
        $this->maxValues = [];
        foreach (Currency::cases() as $currency) {
            $this->minValues []= [
                'currency' => $currency->value,
                'value' => match ($currency->value) {
                    Currency::PLN->value => '100.00',
                    Currency::GBP->value => '25.00',
                    default => '50.00',
                },
            ];
            $this->maxValues []= [
                'currency' => $currency->value,
                'value' => match ($currency->value) {
                    Currency::PLN->value => '500.00',
                    Currency::GBP->value => '125.00',
                    default => '250.00',
                },
            ];
        }

        $this->conditions = [
            [
                'type' => ConditionType::ORDER_VALUE,
                'min_values' => $this->minValues,
                'max_values' => $this->maxValues,
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
                'start_at' => Carbon::now()->toISOString(),
                'end_at' => Carbon::tomorrow()->toISOString(),
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
                'percentage',
                'amounts',
                'priority',
                'uses',
                'condition_groups',
                'target_type',
                'target_products',
                'target_sets',
                'target_shipping_methods',
                'target_is_allow_list',
                'metadata',
                'active',
            ],
        ];
    }

    /**
     * @dataProvider couponOrSaleProvider
     */
    public function testIndexUnauthorized($discountKind): void
    {
        $response = $this->getJson("/{$discountKind}");
        $response->assertForbidden();
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testIndex($user, $discountKind): void
    {
        $this->{$user}->givePermissionTo("{$discountKind}.show");

        $response = $this
            ->actingAs($this->{$user})
            ->getJson("/{$discountKind}");

        $response->assertOk()
            ->assertJsonCount(10, 'data');

        $this->assertQueryCountLessThan(24);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testIndexPerformance($user, $discountKind): void
    {
        $this->{$user}->givePermissionTo("{$discountKind}.show");

        $codes = $discountKind === 'coupons' ? [] : ['code' => null];
        Discount::factory($codes)->count(490)->create();

        $response = $this
            ->actingAs($this->{$user})
            ->getJson("/{$discountKind}?limit=500");

        $response->assertOk()
            ->assertJsonCount(500, 'data');

        // It's now 512 ugh
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
        $this->{$user}->givePermissionTo('coupons.show_details');
        $discount = Discount::factory()->create();

        $response = $this->actingAs($this->{$user})->getJson('/coupons/' . $discount->code);
        $response
            ->assertOk()
            ->assertJsonStructure($this->expectedStructure)
            ->assertJsonFragment([
                'id' => $discount->getKey(),
                'active' => true,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowInactiveByCode($user): void
    {
        $this->{$user}->givePermissionTo('coupons.show_details');
        $discount = Discount::factory()->create([
            'active' => false,
        ]);

        $this
            ->actingAs($this->{$user})
            ->getJson('/coupons/' . $discount->code)
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowWithConditions(string $user): void
    {
        $this->{$user}->givePermissionTo('coupons.show_details');
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

        $response = $this->actingAs($this->{$user})->getJson('/coupons/' . $discount->code);
        $response
            ->assertOk()
            ->assertJsonStructure($this->expectedStructure)
            ->assertJsonFragment([
                'id' => $discount->getKey(),
                'name' => $discount->name,
                'description' => $discount->description,
                'percentage' => $discount->percentage !== null ? number_format($discount->percentage, 4) : null,
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
    public function testShowByIdUnauthorized(string $discountKind): void
    {
        $code = $discountKind === 'coupons' ? [] : ['code' => null];

        $discount = Discount::factory($code)->create();

        $response = $this->getJson("/{$discountKind}/id:" . $discount->getKey());
        $response->assertForbidden();
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testShowById(string $user, string $discountKind): void
    {
        $this->{$user}->givePermissionTo("{$discountKind}.show_details");

        $code = $discountKind === 'coupons' ? [] : ['code' => null];
        $discount = Discount::factory($code)->create();

        $this
            ->actingAs($this->{$user})
            ->getJson("/{$discountKind}/id:" . $discount->getKey())
            ->assertOk()
            ->assertJsonFragment([
                'id' => $discount->getKey(),
                'active' => true,
            ]);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testShowByIdInactive(string $user, string $discountKind): void
    {
        $this->{$user}->givePermissionTo("{$discountKind}.show_details");

        $code = $discountKind === 'coupons' ? [] : ['code' => null];
        $discount = Discount::factory($code)->create([
            'active' => false,
        ]);

        $this
            ->actingAs($this->{$user})
            ->getJson("/{$discountKind}/id:" . $discount->getKey())
            ->assertOk()
            ->assertJsonFragment([
                'id' => $discount->getKey(),
            ]);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testShowInvalidDiscount(string $user, string $discountKind): void
    {
        $this->{$user}->givePermissionTo("{$discountKind}.show_details");

        $code = $discountKind === 'sales' ? [] : ['code' => null];
        $discount = Discount::factory($code)->create();

        $this
            ->actingAs($this->{$user})
            ->json('GET', "/{$discountKind}/id:" . $discount->getKey())
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowWrongCode(string $user): void
    {
        $this->{$user}->givePermissionTo('coupons.show_details');

        /** @var Discount $discount */
        $discount = Discount::factory()->create();

        $this
            ->actingAs($this->{$user})
            ->getJson('/coupons/its_not_code')
            ->assertNotFound();

        $this
            ->actingAs($this->{$user})
            ->getJson('/coupons/' . $discount->code . '_' . $discount->code)
            ->assertNotFound();
    }

    /**
     * @dataProvider couponOrSaleProvider
     */
    public function testCreateUnauthorized($discountKind): void
    {
        Event::fake();

        $response = $this->postJson("/{$discountKind}");
        $response->assertForbidden();

        $event = $discountKind === 'coupons' ? CouponCreated::class : SaleCreated::class;
        Event::assertNotDispatched($event);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testCreateSimple(string $user, string $discountKind): void
    {
        $this->{$user}->givePermissionTo("{$discountKind}.add");

        $event = $discountKind === 'coupons' ? CouponCreated::class : SaleCreated::class;

        Event::fake($event);

        $discount = [
            'translations' => [
                $this->lang => [
                    'name' => 'Coupon',
                    'description' => 'Test coupon',
                    'description_html' => 'html',
                ],
            ],
            'slug' => 'slug',
            'percentage' => '10.0000',
            'priority' => 1,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
            'published' => [
                $this->lang,
            ],
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
            ->actingAs($this->{$user})
            ->json('POST', "/{$discountKind}", $discount + $conditions);

        unset($discount['translations']);
        $response
            ->assertCreated()
            ->assertJsonFragment($discount)
            ->assertJsonFragment([
                'max_uses' => 150,
                'type' => ConditionType::MAX_USES,
            ]);

        $discountId = $response->json('data.id');
        unset($discount['published']);

        $this->assertDatabaseHas(
            'discounts',
            $discount + [
                'id' => $discountId,
                "name->{$this->lang}" => 'Coupon',
                "description->{$this->lang}" => 'Test coupon',
                "description_html->{$this->lang}" => 'html',
            ]
        );
        $this->assertDatabaseCount('condition_groups', 1);
        $this->assertDatabaseHas('discount_condition_groups', ['discount_id' => $discountId]);
        $this->assertDatabaseCount('discount_conditions', 1);

        Event::assertDispatched($event);
        Queue::fake();

        /** @var Discount $discount */
        $discount = Discount::query()->findOrFail($discountId);
        $event = $discountKind === 'coupons' ? new CouponCreated($discount) : new SaleCreated($discount);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Queue::assertNotPushed(CallWebhookJob::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateSalePriceUpdated(string $user): void
    {
        $this->{$user}->givePermissionTo("sales.add");

        Event::fake([SaleCreated::class, ProductPriceUpdated::class]);

        $product = Product::factory()->create([
            'public' => true,
        ]);

        $discount = [
            'translations' => [
                $this->lang => [
                    'name' => 'Coupon',
                    'description' => 'Test coupon',
                    'description_html' => 'html',
                ],
            ],
            'slug' => 'slug',
            'percentage' => '10.0000',
            'priority' => 1,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
            'published' => [
                $this->lang,
            ],
            'target_products' => [
                $product->getKey(),
            ],
        ];

        $this
            ->actingAs($this->{$user})
            ->json('POST', '/sales', $discount)
            ->assertCreated();

        Event::assertDispatched(SaleCreated::class);
        Event::assertDispatched(ProductPriceUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateSimpleWrongCode($user): void
    {
        $this->{$user}->givePermissionTo('coupons.add');

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
            ->actingAs($this->{$user})
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
        $this->{$user}->givePermissionTo("{$discountKind}.add");

        $event = $discountKind === 'coupons' ? CouponCreated::class : SaleCreated::class;

        Event::fake($event);

        $discount = [
            'translations' => [
                $this->lang => [
                    'name' => 'Kupon',
                    'description' => 'Testowy kupon',
                ],
            ],
            'percentage' => '10.0000',
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
            ->actingAs($this->{$user})
            ->json('POST', "/{$discountKind}", $discount);

        unset($discount['translations']);
        $response
            ->assertCreated()
            ->assertJsonFragment($discount + ['name' => 'Kupon', 'description' => 'Testowy kupon']);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testCreateWithMetadataPrivate($user, $discountKind): void
    {
        $this->{$user}->givePermissionTo(["{$discountKind}.add", "{$discountKind}.show_metadata_private"]);

        $event = $discountKind === 'coupons' ? CouponCreated::class : SaleCreated::class;

        Event::fake($event);

        $discount = [
            'translations' => [
                $this->lang => [
                    'name' => 'Kupon',
                    'description' => 'Testowy kupon',
                ],
            ],
            'percentage' => '10.0000',
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
            ->actingAs($this->{$user})
            ->json('POST', "/{$discountKind}", $discount);

        unset($discount['translations']);
        $response
            ->assertCreated()
            ->assertJsonFragment($discount + ['name' => 'Kupon', 'description' => 'Testowy kupon']);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testCreateWithShippingMethod($user, $discountKind): void
    {
        $this->{$user}->givePermissionTo("{$discountKind}.add");

        $event = $discountKind === 'coupons' ? CouponCreated::class : SaleCreated::class;

        Event::fake($event);

        $discount = [
            'translations' => [
                $this->lang => [
                    'name' => 'Kupon',
                    'description' => 'Testowy kupon',
                ],
            ],
            'percentage' => '10.0000',
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
            ->actingAs($this->{$user})
            ->json('POST', "/{$discountKind}", $discount + $shippingMethods);

        unset($discount['translations']);
        $response
            ->assertCreated()
            ->assertJsonFragment($discount + ['name' => 'Kupon', 'description' => 'Testowy kupon'])
            ->assertJsonFragment([
                'id' => $shippingMethod->getKey(),
                'name' => $shippingMethod->name,
                'public' => true,
            ]);

        $discountId = $response->getData()->data->id;

        $this->assertDatabaseHas(
            'discounts',
            $discount + [
                'id' => $discountId,
                "name->{$this->lang}" => 'Kupon',
                "description->{$this->lang}" => 'Testowy kupon',
            ]
        );
        $this->assertDatabaseHas('model_has_discounts', [
            'discount_id' => $discountId,
            'model_type' => $shippingMethod->getMorphClass(),
            'model_id' => $shippingMethod->getKey(),
        ]);
    }

    /**
     * @dataProvider authWithDiscountProvider
     *
     * @throws DtoException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     */
    public function testCreateWithProduct($user, $discountKind): void
    {
        $this->{$user}->givePermissionTo("{$discountKind}.add");

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
            'translations' => [
                $this->lang => [
                    'name' => 'Kupon',
                    'description' => 'Testowy kupon',
                ],
            ],
            'percentage' => '10.0000',
            'priority' => 1,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ];

        if ($discountKind === 'coupons') {
            $discount['code'] = 'S43SA2';
        }

        $product = Product::factory()->create([
            'public' => true,
        ]);
        $this->productRepository->setProductPrices($product->getKey(), [
            ProductPriceType::PRICE_BASE->value => [PriceDto::from(Money::of(1000, $this->currency->value))],
            ProductPriceType::PRICE_MIN_INITIAL->value => [PriceDto::from(Money::of(900, $this->currency->value))],
            ProductPriceType::PRICE_MAX_INITIAL->value => [PriceDto::from(Money::of(1200, $this->currency->value))],
            ProductPriceType::PRICE_MIN->value => [PriceDto::from(Money::of(900, $this->currency->value))],
            ProductPriceType::PRICE_MAX->value => [PriceDto::from(Money::of(1200, $this->currency->value))],
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
            ->actingAs($this->{$user})
            ->json('POST', "/{$discountKind}", $discount + $data);

        unset($discount['translations']);
        $response
            ->assertValid()
            ->assertCreated()
            ->assertJsonFragment($discount + ['name' => 'Kupon', 'description' => 'Testowy kupon'])
            ->assertJsonFragment([
                'id' => $product->getKey(),
                'name' => $product->name,
                'public' => true,
                'prices_min' => [
                    [
                        'currency' => $this->currency->value,
                        'net' => "{$minPriceDiscounted}.00",
                        'gross' => "{$minPriceDiscounted}.00",
                    ],
                ],
                'prices_max' => [
                    [
                        'currency' => $this->currency->value,
                        'net' => "{$maxPriceDiscounted}.00",
                        'gross' => "{$maxPriceDiscounted}.00",
                    ],
                ],
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
            'model_type' => $product->getMorphClass(),
            'model_id' => $product->getKey(),
        ]);
        $this->assertDatabaseHas('model_has_discounts', [
            'discount_id' => $discountId,
            'model_type' => $productSet->getMorphClass(),
            'model_id' => $productSet->getKey(),
        ]);
    }

    /**
     * @dataProvider authWithDiscountProvider
     *
     * @throws DtoException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     */
    public function testCreateWithProductInactive($user, $discountKind): void
    {
        $this->{$user}->givePermissionTo("{$discountKind}.add");

        $minPriceDiscounted = 900;
        $maxPriceDiscounted = 1200;

        Event::fake($discountKind === 'coupons' ? CouponCreated::class : SaleCreated::class);

        $discount = [
            'translations' => [
                $this->lang => [
                    'name' => 'Kupon',
                    'description' => 'Testowy kupon',
                ],
            ],
            'percentage' => '10.0000',
            'priority' => 1,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ];

        if ($discountKind === 'coupons') {
            $discount['code'] = 'S43SA2';
        }

        $product = Product::factory()->create([
            'public' => true,
        ]);
        $this->productRepository->setProductPrices($product->getKey(), [
            ProductPriceType::PRICE_BASE->value => [PriceDto::from(Money::of(1000, $this->currency->value))],
            ProductPriceType::PRICE_MIN_INITIAL->value => [PriceDto::from(Money::of(900, $this->currency->value))],
            ProductPriceType::PRICE_MAX_INITIAL->value => [PriceDto::from(Money::of(1200, $this->currency->value))],
            ProductPriceType::PRICE_MIN->value => [PriceDto::from(Money::of(900, $this->currency->value))],
            ProductPriceType::PRICE_MAX->value => [PriceDto::from(Money::of(1200, $this->currency->value))],
        ]);

        $productSet = ProductSet::factory()->create(['public' => true]);

        $data = [
            'active' => false,
            'target_products' => [
                $product->getKey(),
            ],
            'target_sets' => [
                $productSet->getKey(),
            ],
        ];

        $response = $this
            ->actingAs($this->{$user})
            ->json('POST', "/{$discountKind}", $discount + $data);

        unset($discount['translations']);
        $response
            ->assertCreated()
            ->assertJsonFragment($discount + ['name' => 'Kupon', 'description' => 'Testowy kupon'])
            ->assertJsonFragment([
                'id' => $product->getKey(),
                'name' => $product->name,
                'public' => true,
                'prices_min' => [
                    [
                        'currency' => $this->currency->value,
                        'net' => "{$minPriceDiscounted}.00",
                        'gross' => "{$minPriceDiscounted}.00",
                    ],
                ],
                'prices_max' => [
                    [
                        'currency' => $this->currency->value,
                        'net' => "{$maxPriceDiscounted}.00",
                        'gross' => "{$maxPriceDiscounted}.00",
                    ],
                ],
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
            'model_type' => $product->getMorphClass(),
            'model_id' => $product->getKey(),
        ]);
        $this->assertDatabaseHas('model_has_discounts', [
            'discount_id' => $discountId,
            'model_type' => $productSet->getMorphClass(),
            'model_id' => $productSet->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithProductInChildSet($user): void
    {
        $this->{$user}->givePermissionTo('sales.add');

        Event::fake(SaleCreated::class);

        $discount = [
            'translations' => [
                $this->lang => [
                    'name' => 'Kupon',
                    'description' => 'Testowy kupon',
                ],
            ],
            'percentage' => '10.0000',
            'priority' => 1,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ];

        /** @var Product $product */
        $product = Product::factory()->create([
            'public' => true,
        ]);
        $this->productRepository->setProductPrices($product->getKey(), [
            ProductPriceType::PRICE_BASE->value => [PriceDto::from(Money::of(1000, $this->currency->value))],
            ProductPriceType::PRICE_MIN_INITIAL->value => [PriceDto::from(Money::of(900, $this->currency->value))],
            ProductPriceType::PRICE_MAX_INITIAL->value => [PriceDto::from(Money::of(1200, $this->currency->value))],
        ]);

        $parentSet = ProductSet::factory()->create(['public' => true]);
        $childSet = ProductSet::factory()->create([
            'public' => true,
            'public_parent' => true,
            'parent_id' => $parentSet->getKey(),
        ]);
        $subChildSet = ProductSet::factory()->create([
            'public' => true,
            'public_parent' => true,
            'parent_id' => $childSet->getKey(),
        ]);

        $product->sets()->sync([$subChildSet->getKey()]);

        $data = [
            'target_sets' => [
                $parentSet->getKey(),
            ],
        ];

        $response = $this
            ->actingAs($this->{$user})
            ->json('POST', '/sales', $discount + $data);

        unset($discount['translations']);
        $response
            ->assertValid()
            ->assertCreated()
            ->assertJsonFragment($discount + ['name' => 'Kupon', 'description' => 'Testowy kupon'])
            ->assertJsonFragment([
                'id' => $parentSet->getKey(),
                'name' => $parentSet->name,
                'public' => true,
            ]);

        $discountId = $response->json('data.id');

        $this->assertDatabaseHas('discounts', $discount + ['id' => $discountId]);
        $this->assertDatabaseHas('model_has_discounts', [
            'discount_id' => $discountId,
            'model_type' => $parentSet->getMorphClass(),
            'model_id' => $parentSet->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithProductInChildSetInactive($user): void
    {
        $this->{$user}->givePermissionTo('sales.add');

        Event::fake(SaleCreated::class);

        $discount = [
            'translations' => [
                $this->lang => [
                    'name' => 'Kupon',
                    'description' => 'Testowy kupon',
                ],
            ],
            'percentage' => '10.0000',
            'priority' => 1,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ];

        /** @var Product $product */
        $product = Product::factory()->create([
            'public' => true,
        ]);
        $this->productRepository->setProductPrices($product->getKey(), [
            ProductPriceType::PRICE_BASE->value => [PriceDto::from(Money::of(1000, $this->currency->value))],
            ProductPriceType::PRICE_MIN_INITIAL->value => [PriceDto::from(Money::of(900, $this->currency->value))],
            ProductPriceType::PRICE_MAX_INITIAL->value => [PriceDto::from(Money::of(1200, $this->currency->value))],
        ]);

        $parentSet = ProductSet::factory()->create(['public' => true]);
        $childSet = ProductSet::factory()->create([
            'public' => true,
            'public_parent' => true,
            'parent_id' => $parentSet->getKey(),
        ]);
        $subChildSet = ProductSet::factory()->create([
            'public' => true,
            'public_parent' => true,
            'parent_id' => $childSet->getKey(),
        ]);

        $product->sets()->sync([$subChildSet->getKey()]);

        $data = [
            'active' => false,
            'target_sets' => [
                $parentSet->getKey(),
            ],
        ];

        $response = $this
            ->actingAs($this->{$user})
            ->json('POST', '/sales', $discount + $data);

        unset($discount['translations']);
        $response
            ->assertCreated()
            ->assertJsonFragment($discount + ['name' => 'Kupon', 'description' => 'Testowy kupon'])
            ->assertJsonFragment([
                'id' => $parentSet->getKey(),
                'name' => $parentSet->name,
                'public' => true,
            ]);

        $discountId = $response->getData()->data->id;

        $this->assertDatabaseHas('discounts', $discount + ['id' => $discountId]);
        $this->assertDatabaseHas('model_has_discounts', [
            'discount_id' => $discountId,
            'model_type' => $parentSet->getMorphClass(),
            'model_id' => $parentSet->getKey(),
        ]);
        $this->assertDatabaseHas('prices', [
            'model_id' => $product->getKey(),
            'price_type' => ProductPriceType::PRICE_MIN->value,
            'value' => 90000,
        ]);
        $this->assertDatabaseHas('prices', [
            'model_id' => $product->getKey(),
            'price_type' => ProductPriceType::PRICE_MAX->value,
            'value' => 120000,
        ]);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testCreateNoDescription($user, $discountKind): void
    {
        $this->{$user}->givePermissionTo("{$discountKind}.add");

        Queue::fake();

        $discount = [
            'translations' => [
                $this->lang => [
                    'name' => 'Kupon',
                ],
            ],
            'percentage' => '10.0000',
            'priority' => 1,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
        ];

        if ($discountKind === 'coupons') {
            $discount['code'] = 'S43SA2';
        }

        $response = $this->actingAs($this->{$user})->json(
            'POST',
            "/{$discountKind}",
            $discount + ['description' => '']
        );

        unset($discount['translations']);
        $response
            ->assertCreated()
            ->assertJsonFragment($discount + ['name' => 'Kupon', 'description' => '']);

        $this->assertDatabaseHas('discounts', $discount + ['id' => $response->json('data.id')]);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testCreateMaxValuePercentage($user, $discountKind): void
    {
        $this->{$user}->givePermissionTo("{$discountKind}.add");

        Queue::fake();

        $discount = [
            'translations' => [
                $this->lang => [
                    'name' => 'Kupon',
                ],
            ],
            'percentage' => '855',
            'priority' => 1,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
        ];

        if ($discountKind === 'coupons') {
            $discount['code'] = 'S43SA2';
        }

        $this
            ->actingAs($this->{$user})
            ->json('POST', "/{$discountKind}", $discount)
            ->assertStatus(422);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testCreateMaxValueAmount($user, $discountKind): void
    {
        $this->{$user}->givePermissionTo("{$discountKind}.add");

        Queue::fake();

        $discount = [
            'translations' => [
                $this->lang => [
                    'name' => 'Kupon',
                ],
            ],
            'amounts' => Arr::map(Currency::values(), fn (string $currency) => [
                'value' => '855.00',
                'currency' => $currency,
            ]),
            'type' => DiscountType::AMOUNT,
            'priority' => 1,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
        ];

        if ($discountKind === 'coupons') {
            $discount['code'] = 'S43SA2';
        }

        $response = $this->actingAs($this->{$user})->json('POST', "/{$discountKind}", $discount);

        unset($discount['translations']);
        unset($discount['type']);

        $discount['amounts'] = Arr::map($discount['amounts'], fn (array $amount) => [
            'currency' => $amount['currency'],
            'gross' => $amount['value'],
            'net' => $amount['value'],
        ]);

        $response
            ->assertValid()
            ->assertCreated()
            ->assertJsonFragment($discount + ['name' => 'Kupon']);

        $discountId = $response->getData()->data->id;

        $this->assertDatabaseHas('discounts', [
            'id' => $discountId,
            'code' => $discount['code'] ?? null,
            'priority' => $discount['priority'],
            'target_type' => $discount['target_type']->value,
            'target_is_allow_list' => $discount['target_is_allow_list'],
        ]);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testCreateMinValuePercentage($user, $discountKind): void
    {
        $this->{$user}->givePermissionTo("{$discountKind}.add");

        Queue::fake();

        $discount = [
            'translations' => [
                $this->lang => [
                    'name' => 'Kupon',
                ],
            ],
            'percentage' => '-10',
            'priority' => 1,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
        ];

        if ($discountKind === 'coupons') {
            $discount['code'] = 'S43SA2';
        }

        $response = $this->actingAs($this->{$user})->json('POST', "/{$discountKind}", $discount);

        $response
            ->assertStatus(422);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testCreateMinValueAmount($user, $discountKind): void
    {
        $this->{$user}->givePermissionTo("{$discountKind}.add");

        Queue::fake();

        $discount = [
            'translations' => [
                $this->lang => [
                    'name' => 'Kupon',
                ],
            ],
            'amounts' => Arr::map(Currency::values(), fn (string $currency) => [
                'value' => '-10.00',
                'currency' => $currency,
            ]),
            'priority' => 1,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
        ];

        if ($discountKind === 'coupons') {
            $discount['code'] = 'S43SA2';
        }

        $this
            ->actingAs($this->{$user})->json('POST', "/{$discountKind}", $discount)
            ->assertUnprocessable()
            ->assertJsonFragment([
                'message' => 'The amounts.0 value is less than defined minimum: 0',
            ]);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testCreateFull($user, $discountKind): void
    {
        $this->{$user}->givePermissionTo("{$discountKind}.add");

        $discount = [
            'translations' => [
                $this->lang => [
                    'name' => 'Kupon',
                    'description' => 'Testowy kupon',
                ],
            ],
            'percentage' => '10.0000',
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

        $response = $this->actingAs($this->{$user})->json('POST', "/{$discountKind}", $discount + $conditions);

        unset($discount['translations']);
        $response
            ->assertValid()
            ->assertCreated()
            ->assertJsonFragment($discount + ['name' => 'Kupon', 'description' => 'Testowy kupon']);

        foreach ($this->conditions as $condition) {
            if (
                !in_array(
                    $condition['type'],
                    [
                        ConditionType::USER_IN_ROLE,
                        ConditionType::USER_IN,
                        ConditionType::PRODUCT_IN,
                        ConditionType::PRODUCT_IN_SET,
                        ConditionType::ORDER_VALUE,
                    ],
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
            ])
            ->assertJsonFragment([
                'type' => ConditionType::ORDER_VALUE,
                'include_taxes' => false,
                'is_in_range' => true,
                'min_values' => array_map(fn ($value) => [
                    'currency' => $value['currency'],
                    'net' => $value['value'],
                    'gross' => $value['value'],
                ], $this->minValues),
                'max_values' => array_map(fn ($value) => [
                    'currency' => $value['currency'],
                    'net' => $value['value'],
                    'gross' => $value['value'],
                ], $this->maxValues),
            ]);

        $discountId = $response->getData()->data->id;

        $this->assertDatabaseHas('discounts', $discount + ['id' => $discountId]);
        $this->assertDatabaseCount('condition_groups', 1);
        $this->assertDatabaseHas('discount_condition_groups', ['discount_id' => $discountId]);
        $this->assertDatabaseCount('discount_conditions', count($this->conditions));
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testCreateWeekdayInCondition($user, $discountKind): void
    {
        $this->{$user}->givePermissionTo("{$discountKind}.add");

        $discount = [
            'translations' => [
                $this->lang => [
                    'name' => 'Kupon',
                    'description' => 'Testowy kupon',
                ],
            ],
            'percentage' => '10.0000',
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

        $response = $this->actingAs($this->{$user})->json('POST', "/{$discountKind}", $discount + $conditions);

        unset($discount['translations']);
        $response
            ->assertValid()
            ->assertCreated()
            ->assertJsonFragment($discount + ['name' => 'Kupon', 'description' => 'Testowy kupon'])
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
        $this->{$user}->givePermissionTo("{$discountKind}.add");

        $discount = [
            'translations' => [
                $this->lang => [
                    'name' => 'Kupon',
                    'description' => 'Testowy kupon',
                ],
            ],
            'percentage' => '10.0000',
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
                            'start_at' => '2022-04-15T12:44:40.130Z',
                            'end_at' => '2022-04-20T12:44:40.130Z',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->{$user})->json('POST', "/{$discountKind}", $discount + $conditions);

        unset($discount['translations']);
        $response
            ->assertCreated()
            ->assertJsonFragment($discount + ['name' => 'Kupon', 'description' => 'Testowy kupon'])
            ->assertJsonFragment([
                'type' => ConditionType::DATE_BETWEEN,
                'is_in_range' => true,
                'start_at' => '2022-04-15T12:44:40.130000Z',
                'end_at' => '2022-04-20T12:44:40.130000Z',
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

    /**
     * @dataProvider timeConditionProvider
     */
    public function testCreateSaleAddToCache($user, $condition): void
    {
        Carbon::setTestNow('2022-05-12T12:00:00'); // Thursday
        $this->{$user}->givePermissionTo('sales.add');

        $discount = [
            'translations' => [
                $this->lang => [
                    'name' => 'Sale',
                    'description' => 'Test sale',
                ],
            ],
            'percentage' => '10.0000',
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

        $response = $this->actingAs($this->{$user})->json('POST', 'sales', $discount + $conditions);

        $response->assertValid()->assertCreated();

        $discountModel = Discount::find($response->getData()->data->id);

        $activeSales = Cache::get('sales.active');
        $this->assertCount(1, $activeSales);
        $this->assertTrue($activeSales->contains($discountModel->getKey()));
    }

    /**
     * @dataProvider timeConditionProvider
     *
     * @throws DtoException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     */
    public function testCreateInactiveSaleNoAddToCache($user, $condition): void
    {
        Carbon::setTestNow('2022-05-12T12:00:00'); // Thursday
        $this->{$user}->givePermissionTo('sales.add');

        $product = Product::factory()->create([
            'public' => true,
        ]);
        $this->productRepository->setProductPrices($product->getKey(), [
            ProductPriceType::PRICE_BASE->value => [PriceDto::from(Money::of(1000, $this->currency->value))],
            ProductPriceType::PRICE_MIN_INITIAL->value => [PriceDto::from(Money::of(1000, $this->currency->value))],
            ProductPriceType::PRICE_MAX_INITIAL->value => [PriceDto::from(Money::of(1000, $this->currency->value))],
        ]);

        $discount = [
            'translations' => [
                $this->lang => [
                    'name' => 'Sale',
                    'description' => 'Test sale',
                ],
            ],
            'percentage' => '10.0000',
            'priority' => 1,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
            'code' => null,
            'active' => false,
            'target_products' => [
                $product->getKey(),
            ],
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

        $response = $this->actingAs($this->{$user})->json('POST', 'sales', $discount + $conditions);

        $response->assertCreated();

        $this->assertDatabaseHas('prices', [
            'model_id' => $product->getKey(),
            'price_type' => ProductPriceType::PRICE_MIN->value,
            'value' => 100000,
        ]);
        $this->assertDatabaseHas('prices', [
            'model_id' => $product->getKey(),
            'price_type' => ProductPriceType::PRICE_MAX->value,
            'value' => 100000,
        ]);

        $discountModel = Discount::find($response->getData()->data->id);

        $activeSales = Cache::get('sales.active');
        $this->assertCount(0, $activeSales);
        $this->assertFalse($activeSales->contains($discountModel->getKey()));
    }

    /**
     * @dataProvider timeConditionProvider
     */
    public function testCreateSaleNoAddToCache($user, $condition): void
    {
        Carbon::setTestNow('2022-05-20T16:00:00'); // Friday
        $this->{$user}->givePermissionTo('sales.add');

        $discount = [
            'translations' => [
                $this->lang => [
                    'name' => 'Sale',
                    'description' => 'Test sale',
                ],
            ],
            'percentage' => '10.0000',
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

        $response = $this->actingAs($this->{$user})->json('POST', 'sales', $discount + $conditions);

        $response->assertValid()->assertCreated();

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
        $this->{$user}->givePermissionTo("{$discountKind}.add");

        $discount = [
            'translations' => [
                $this->lang => [
                    'name' => 'Kupon',
                    'description' => 'Testowy kupon',
                ],
            ],
            'percentage' => '10.0000',
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

        $response = $this->actingAs($this->{$user})->json('POST', "/{$discountKind}", $discount);

        $response
            ->assertStatus(422);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testCreateWithWebHookEvent($user, $discountKind): void
    {
        $this->{$user}->givePermissionTo("{$discountKind}.add");

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
            'model_type' => $this->{$user}::class,
            'creator_id' => $this->{$user}->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Event::fake($event);

        $response = $this->actingAs($this->{$user})->json(
            'POST',
            "/{$discountKind}",
            [
                'translations' => [
                    $this->lang => [
                        'name' => 'Kupon',
                        'description' => 'Testowy kupon',
                    ],
                ],
                'percentage' => '10.0000',
                'priority' => 1,
                'target_type' => DiscountTargetType::ORDER_VALUE,
                'target_is_allow_list' => true,
            ] + $code
        )
            ->assertCreated();

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
     * @dataProvider authWithDiscountProvider
     */
    public function testCreateExistingSlug(string $user, string $discountKind): void
    {
        $this->{$user}->givePermissionTo("{$discountKind}.add");

        Discount::factory()->create(['slug' => 'existing-slug']);

        $discount = [
            'translations' => [
                $this->lang => [
                    'name' => 'Coupon',
                    'description' => 'Test coupon',
                    'description_html' => 'html',
                ],
            ],
            'slug' => 'existing-slug',
            'percentage' => '10.0000',
            'priority' => 1,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
            'published' => [
                $this->lang,
            ],
        ];

        if ($discountKind === 'coupons') {
            $discount['code'] = 'S43SA2';
        }

        $this
            ->actingAs($this->{$user})
            ->json('POST', "/{$discountKind}", $discount)
            ->assertUnprocessable()
            ->assertJsonFragment([
                'key' => ValidationError::UNIQUE,
                'message' => 'The slug has already been taken.'
            ]);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testCreateExistingSlugDeleted(string $user, string $discountKind): void
    {
        $this->{$user}->givePermissionTo("{$discountKind}.add");

        $existing = Discount::factory()->create(['slug' => 'existing-slug']);
        $existing->delete();

        $discount = [
            'translations' => [
                $this->lang => [
                    'name' => 'Coupon',
                    'description' => 'Test coupon',
                    'description_html' => 'html',
                ],
            ],
            'slug' => 'existing-slug',
            'percentage' => '10.0000',
            'priority' => 1,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
            'published' => [
                $this->lang,
            ],
        ];

        if ($discountKind === 'coupons') {
            $discount['code'] = 'S43SA2';
        }

        $this
            ->actingAs($this->{$user})
            ->json('POST', "/{$discountKind}", $discount)
            ->assertCreated()
            ->assertJsonFragment([
                'slug' => 'existing-slug',
            ]);
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
            ->patchJson("/{$discountKind}/id:" . $discount->getKey())
            ->assertForbidden();

        $event = $discountKind === 'coupons' ? CouponUpdated::class : SaleUpdated::class;
        Event::assertNotDispatched($event);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testUpdateInvalidDiscount($user, $discountKind): void
    {
        $this->{$user}->givePermissionTo("{$discountKind}.edit");

        $code = $discountKind === 'sales' ? [] : ['code' => null];
        $discount = Discount::factory($code)->create();

        Event::fake();

        $this
            ->actingAs($this->{$user})
            ->patchJson("/{$discountKind}/id:" . $discount->getKey(), [
                'code' => 'S43SA2',
            ])
            ->assertNotFound();

        $event = $discountKind === 'coupons' ? CouponCreated::class : SaleCreated::class;
        Event::assertNotDispatched($event);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testUpdateFull($user, $discountKind): void
    {
        $this->{$user}->givePermissionTo("{$discountKind}.edit");
        $code = $discountKind === 'coupons' ? [] : ['code' => null];
        $discount = Discount::factory(['target_type' => DiscountTargetType::ORDER_VALUE, 'slug' => 'slug'] + $code)->create();

        $conditionGroup = ConditionGroup::create();
        $discountCondition = $conditionGroup->conditions()->create(
            [
                'type' => ConditionType::MAX_USES,
                'value' => ['max_uses' => 1000],
            ],
        );

        $discount->conditionGroups()->attach($conditionGroup);

        $discountNew = [
            'translations' => [
                $this->lang => [
                    'name' => 'Kupon',
                    'description' => 'Testowy kupon',
                    'description_html' => 'html',
                ],
            ],
            'slug' => 'slug',
            'percentage' => '10.0000',
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

        $response = $this->actingAs($this->{$user})
            ->json('PATCH', "/{$discountKind}/id:" . $discount->getKey(), $discountNew + $conditions);

        unset($discountNew['translations']);
        $response
            ->assertValid()
            ->assertOk()
            ->assertJsonFragment(
                $discountNew + [
                    'id' => $discount->getKey(),
                    'name' => 'Kupon',
                    'description' => 'Testowy kupon',
                    'description_html' => 'html',
                ]
            )
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
                        ConditionType::ORDER_VALUE,
                    ],
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
            ])
            ->assertJsonFragment([
                'type' => ConditionType::ORDER_VALUE,
                'include_taxes' => false,
                'is_in_range' => true,
                'min_values' => array_map(fn ($value) => [
                    'currency' => $value['currency'],
                    'net' => $value['value'],
                    'gross' => $value['value'],
                ], $this->minValues),
                'max_values' => array_map(fn ($value) => [
                    'currency' => $value['currency'],
                    'net' => $value['value'],
                    'gross' => $value['value'],
                ], $this->maxValues),
            ]);

        $this->assertDatabaseHas('discounts', $discountNew + ['id' => $discount->getKey()]);
        $this->assertDatabaseCount('condition_groups', 1);
        $this->assertDatabaseCount('discount_condition_groups', 1);
        $this->assertDatabaseHas('discount_condition_groups', ['discount_id' => $discount->getKey()]);
        $this->assertDatabaseCount('discount_conditions', count($this->conditions));

        Queue::assertPushed(CallQueuedListener::class, fn ($job) => $job->class === WebHookEventListener::class);

        $discount = Discount::find($discount->getKey());
        $event = $discountKind === 'coupons' ? new CouponCreated($discount) : new SaleCreated($discount);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Queue::assertNotPushed(CallWebhookJob::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateSalePriceUpdated($user): void
    {
        $this->{$user}->givePermissionTo('sales.edit');
        $discount = Discount::factory(['target_type' => DiscountTargetType::PRODUCTS, 'code' => null])->create();

        $product = Product::factory()->create([
            'public' => true,
        ]);

        Event::fake([SaleUpdated::class, ProductPriceUpdated::class]);

        $discountNew = [
            'translations' => [
                $this->lang => [
                    'name' => 'Kupon',
                    'description' => 'Testowy kupon',
                    'description_html' => 'html',
                ],
            ],
            'slug' => 'slug',
            'percentage' => '10.0000',
            'priority' => 1,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
            'target_products' => [
                $product->getKey(),
            ],
        ];

        $this->actingAs($this->{$user})
            ->json('PATCH', '/sales/id:' . $discount->getKey(), $discountNew)
            ->assertValid()
            ->assertOk();

        Event::assertDispatched(SaleUpdated::class);
        Event::assertDispatched(ProductPriceUpdated::class);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testUpdateOrderValueConditionWithNull($user, $discountKind): void
    {
        $this->{$user}->givePermissionTo("{$discountKind}.edit");
        $code = $discountKind === 'coupons' ? [] : ['code' => null];
        $discount = Discount::factory(['target_type' => DiscountTargetType::ORDER_VALUE] + $code)->create();

        $conditionGroup = ConditionGroup::create();

        $discountValue = [
            'min_values' => $this->minValues,
            'max_values' => $this->maxValues,
            'include_taxes' => false,
            'is_in_range' => true,
        ];

        /** @var DiscountCondition $condition */
        $condition = DiscountCondition::create([
            'condition_group_id' => $conditionGroup->getKey(),
            'type' => ConditionType::ORDER_VALUE,
            'value' => $discountValue,
        ]);
        $condition->pricesMin()->create([
            'value' => 10000,
            'currency' => Currency::PLN->value,
            'price_type' => DiscountConditionPriceType::PRICE_MIN->value,
        ]);
        $condition->pricesMin()->create([
            'value' => 2500,
            'currency' => Currency::GBP->value,
            'price_type' => DiscountConditionPriceType::PRICE_MIN->value,
        ]);
        $condition->pricesMax()->create([
            'value' => 50000,
            'currency' => Currency::PLN->value,
            'price_type' => DiscountConditionPriceType::PRICE_MAX->value,
        ]);
        $condition->pricesMin()->create([
            'value' => 12500,
            'currency' => Currency::GBP->value,
            'price_type' => DiscountConditionPriceType::PRICE_MAX->value,
        ]);

        $discount->conditionGroups()->attach($conditionGroup);

        $discountNew = [
            'condition_groups' => [
                [
                    'id' => $conditionGroup->getKey(),
                    'conditions' => [
                        [
                            'id' => $condition->getKey(),
                            'type' => ConditionType::ORDER_VALUE,
                            'min_values' => null,
                            'max_values' => $this->maxValues,
                            'include_taxes' => false,
                            'is_in_range' => true,
                        ],
                    ]
                ],
            ],
        ];

        Queue::fake();

        $response = $this->actingAs($this->{$user})
            ->json('PATCH', "/{$discountKind}/id:" . $discount->getKey(), $discountNew);

        $response
            ->assertValid()
            ->assertOk();

        $response->assertJsonFragment([
            'type' => ConditionType::ORDER_VALUE,
            'include_taxes' => false,
            'is_in_range' => true,
            'min_values' => null,
            'max_values' => array_map(fn ($value) => [
                'currency' => $value['currency'],
                'net' => $value['value'],
                'gross' => $value['value'],
            ], $this->maxValues),
        ]);

        $this->assertDatabaseHas('discounts', ['id' => $discount->getKey()]);
        $this->assertDatabaseCount('condition_groups', 1);
        $this->assertDatabaseCount('discount_condition_groups', 1);
        $this->assertDatabaseHas('discount_condition_groups', ['discount_id' => $discount->getKey()]);

        Queue::assertPushed(CallQueuedListener::class, fn ($job) => $job->class === WebHookEventListener::class);

        $discount = Discount::find($discount->getKey());
        $event = $discountKind === 'coupons' ? new CouponCreated($discount) : new SaleCreated($discount);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Queue::assertNotPushed(CallWebhookJob::class);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testUpdateWithPartialData($user, $discountKind): void
    {
        $this->{$user}->givePermissionTo("{$discountKind}.edit");
        $code = $discountKind === 'coupons' ? [] : ['code' => null];

        $discount = Discount::factory()->create($code);

        Queue::fake();

        $response = $this->actingAs($this->{$user})
            ->json('PATCH', "/{$discountKind}/id:" . $discount->getKey(), [
                'amounts' => array_map(fn (string $currency) => [
                    'value' => '50.00',
                    'currency' => $currency,
                ], Currency::values()),
            ]);

        $code = $discountKind === 'coupons' ? ['code' => $discount->code] : [];

        $response
            ->assertOk()
            ->assertJsonFragment(
                [
                    'id' => $discount->getKey(),
                    'amounts' => array_map(fn (string $currency) => [
                        'currency' => $currency,
                        'net' => '50.00',
                        'gross' => '50.00',
                    ], Currency::values()),
                    'metadata' => [],
                ] + $code
            );

        $this->assertDatabaseHas(
            'prices',
            [
                'model_id' => $discount->getKey(),
                'model_type' => $discount->getMorphClass(),
                'value' => 5000,
                'price_type' => 'amount',
                'currency' => $this->currency->value,
            ]
        );

        Queue::assertNotPushed(CallWebhookJob::class);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testUpdateWithWebHookQueue($user, $discountKind): void
    {
        $this->{$user}->givePermissionTo("{$discountKind}.edit");

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
            'model_type' => $this->{$user}::class,
            'creator_id' => $this->{$user}->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Queue::fake();

        $this->actingAs($this->{$user})
            ->json('PATCH', "/{$discountKind}/id:" . $discount->getKey(), [
                'description' => 'Weekend Sale',
                'discount' => 20,
                'type' => DiscountType::AMOUNT,
                'code' => $discount->code,
            ])->assertOk();

        Queue::assertPushed(CallQueuedListener::class, fn ($job) => $job->class === WebHookEventListener::class);

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
        $this->{$user}->givePermissionTo("{$discountKind}.edit");

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
            'model_type' => $this->{$user}::class,
            'creator_id' => $this->{$user}->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Bus::fake();

        $this->actingAs($this->{$user})
            ->json('PATCH', "/{$discountKind}/id:" . $discount->getKey(), [
                'description' => 'Weekend Sale',
                'discount' => 20,
                'type' => DiscountType::AMOUNT,
                'code' => $discount->code,
            ])->assertOk();

        Bus::assertDispatched(CallQueuedListener::class, fn ($job) => $job->class === WebHookEventListener::class);

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
     *
     * @throws DtoException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     */
    public function testUpdateSaleWithProduct($user): void
    {
        $this->{$user}->givePermissionTo('sales.edit');
        $discount = Discount::factory(['target_type' => DiscountTargetType::PRODUCTS, 'code' => null, 'percentage' => null])->create();

        $product1 = Product::factory()->create([
            'public' => true,
        ]);
        $this->productRepository->setProductPrices($product1->getKey(), [
            ProductPriceType::PRICE_BASE->value => [PriceDto::from(Money::of(100, $this->currency->value))],
            ProductPriceType::PRICE_MIN_INITIAL->value => [PriceDto::from(Money::of(100, $this->currency->value))],
            ProductPriceType::PRICE_MAX_INITIAL->value => [PriceDto::from(Money::of(150, $this->currency->value))],
            ProductPriceType::PRICE_MIN->value => [PriceDto::from(Money::of(100, $this->currency->value))],
            ProductPriceType::PRICE_MAX->value => [PriceDto::from(Money::of(150, $this->currency->value))],
        ]);

        $product2 = Product::factory()->create([
            'public' => true,
        ]);
        $this->productRepository->setProductPrices($product2->getKey(), [
            ProductPriceType::PRICE_BASE->value => [PriceDto::from(Money::of(200, $this->currency->value))],
            ProductPriceType::PRICE_MIN_INITIAL->value => [PriceDto::from(Money::of(150, $this->currency->value))],
            ProductPriceType::PRICE_MAX_INITIAL->value => [PriceDto::from(Money::of(190, $this->currency->value))],
            ProductPriceType::PRICE_MIN->value => [PriceDto::from(Money::of(150, $this->currency->value))],
            ProductPriceType::PRICE_MAX->value => [PriceDto::from(Money::of(190, $this->currency->value))],
        ]);

        $product3 = Product::factory()->create([
            'public' => true,
        ]);
        $this->productRepository->setProductPrices($product3->getKey(), [
            ProductPriceType::PRICE_BASE->value => [PriceDto::from(Money::of(300, $this->currency->value))],
            ProductPriceType::PRICE_MIN_INITIAL->value => [PriceDto::from(Money::of(290, $this->currency->value))],
            ProductPriceType::PRICE_MAX_INITIAL->value => [PriceDto::from(Money::of(350, $this->currency->value))],
            ProductPriceType::PRICE_MIN->value => [PriceDto::from(Money::of(290, $this->currency->value))],
            ProductPriceType::PRICE_MAX->value => [PriceDto::from(Money::of(350, $this->currency->value))],
        ]);

        $discount->products()->sync([$product1->getKey(), $product2->getKey()]);

        /** @var DiscountServiceContract $discountService */
        $discountService = App::make(DiscountServiceContract::class);

        // Apply discount to products before update
        $discountService->applyDiscountsOnProducts(Collection::make([$product1, $product2, $product3]));

        $discountNew = [
            'translations' => [
                $this->lang => [
                    'name' => 'Kupon',
                    'description' => 'Testowy kupon',
                ],
            ],
            'amounts' => array_map(fn (string $currency) => [
                'value' => '10.00',
                'currency' => $currency,
            ], Currency::values()),
            'priority' => 1,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ];

        $data = [
            'active' => true,
            'target_products' => [
                $product2->getKey(),
                $product3->getKey(),
            ],
        ];

        $response = $this->actingAs($this->{$user})
            ->json('PATCH', '/sales/id:' . $discount->getKey(), $discountNew + $data)
            ->assertOk();

        unset($discountNew['translations'], $discountNew['amounts']);
        $response
            ->assertJsonFragment($discountNew + [
                'id' => $discount->getKey(),
                'amounts' => array_map(fn (string $currency) => [
                    'net' => '10.00',
                    'gross' => '10.00',
                    'currency' => $currency,
                ], Currency::values()),
            ])
            ->assertJsonFragment([
                'id' => $product2->getKey(),
                'prices_base' => [
                    [
                        'currency' => $this->currency->value,
                        'gross' => '200.00',
                        'net' => '200.00',
                    ],
                ],
                'prices_min_initial' => [
                    [
                        'currency' => $this->currency->value,
                        'gross' => '150.00',
                        'net' => '150.00',
                    ],
                ],
                'prices_max_initial' => [
                    [
                        'currency' => $this->currency->value,
                        'gross' => '190.00',
                        'net' => '190.00',
                    ],
                ],
                'prices_min' => [
                    [
                        'currency' => $this->currency->value,
                        'gross' => '140.00',
                        'net' => '140.00',
                    ],
                ],
                'prices_max' => [
                    [
                        'currency' => $this->currency->value,
                        'gross' => '180.00',
                        'net' => '180.00',
                    ],
                ],
            ])
            ->assertJsonFragment([
                'id' => $product3->getKey(),
                'prices_base' => [
                    [
                        'currency' => $this->currency->value,
                        'gross' => '300.00',
                        'net' => '300.00',
                    ],
                ],
                'prices_min_initial' => [
                    [
                        'currency' => $this->currency->value,
                        'gross' => '290.00',
                        'net' => '290.00',
                    ],
                ],
                'prices_max_initial' => [
                    [
                        'currency' => $this->currency->value,
                        'gross' => '350.00',
                        'net' => '350.00',
                    ],
                ],
                'prices_min' => [
                    [
                        'currency' => $this->currency->value,
                        'gross' => '280.00',
                        'net' => '280.00',
                    ],
                ],
                'prices_max' => [
                    [
                        'currency' => $this->currency->value,
                        'gross' => '340.00',
                        'net' => '340.00',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('discounts', $discountNew + ['id' => $discount->getKey()]);

        $this->assertDatabaseMissing('product_sales', [
            'product_id' => $product1->getKey(),
            'sale_id' => $discount->getKey(),
        ]);

        $this->assertProductPrices($product1->getKey(), [
            ProductPriceType::PRICE_BASE->value => 100,
            ProductPriceType::PRICE_MIN_INITIAL->value => 100,
            ProductPriceType::PRICE_MAX_INITIAL->value => 150,
            ProductPriceType::PRICE_MIN->value => 100,
            ProductPriceType::PRICE_MAX->value => 150,
        ]);

        $this->assertProductPrices($product2->getKey(), [
            ProductPriceType::PRICE_BASE->value => 200,
            ProductPriceType::PRICE_MIN_INITIAL->value => 150,
            ProductPriceType::PRICE_MAX_INITIAL->value => 190,
            ProductPriceType::PRICE_MIN->value => 140,
            ProductPriceType::PRICE_MAX->value => 180,
        ]);

        $this->assertProductPrices($product3->getKey(), [
            ProductPriceType::PRICE_BASE->value => 300,
            ProductPriceType::PRICE_MIN_INITIAL->value => 290,
            ProductPriceType::PRICE_MAX_INITIAL->value => 350,
            ProductPriceType::PRICE_MIN->value => 280,
            ProductPriceType::PRICE_MAX->value => 340,
        ]);
    }

    /**
     * @dataProvider authProvider
     *
     * @throws DtoException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     */
    public function testUpdateInactiveSaleWithProduct($user): void
    {
        $this->{$user}->givePermissionTo('sales.edit');

        $discountData = [
            'name' => 'Kupon',
            'description' => 'Testowy kupon',
            'priority' => 1,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ];

        $discount = Discount::factory(
            $discountData + [
                'code' => null,
                'active' => true,
                'percentage' => null,
            ]
        )->create();

        $product1 = Product::factory()->create([
            'public' => true,
        ]);
        $this->productRepository->setProductPrices($product1->getKey(), [
            ProductPriceType::PRICE_BASE->value => [PriceDto::from(Money::of(100, $this->currency->value))],
            ProductPriceType::PRICE_MIN_INITIAL->value => [PriceDto::from(Money::of(100, $this->currency->value))],
            ProductPriceType::PRICE_MAX_INITIAL->value => [PriceDto::from(Money::of(150, $this->currency->value))],
            ProductPriceType::PRICE_MIN->value => [PriceDto::from(Money::of(100, $this->currency->value))],
            ProductPriceType::PRICE_MAX->value => [PriceDto::from(Money::of(150, $this->currency->value))],
        ]);

        $product2 = Product::factory()->create([
            'public' => true,
        ]);
        $this->productRepository->setProductPrices($product2->getKey(), [
            ProductPriceType::PRICE_BASE->value => [PriceDto::from(Money::of(200, $this->currency->value))],
            ProductPriceType::PRICE_MIN_INITIAL->value => [PriceDto::from(Money::of(190, $this->currency->value))],
            ProductPriceType::PRICE_MAX_INITIAL->value => [PriceDto::from(Money::of(250, $this->currency->value))],
            ProductPriceType::PRICE_MIN->value => [PriceDto::from(Money::of(190, $this->currency->value))],
            ProductPriceType::PRICE_MAX->value => [PriceDto::from(Money::of(250, $this->currency->value))],
        ]);

        $product3 = Product::factory()->create([
            'public' => true,
        ]);
        $this->productRepository->setProductPrices($product3->getKey(), [
            ProductPriceType::PRICE_BASE->value => [PriceDto::from(Money::of(300, $this->currency->value))],
            ProductPriceType::PRICE_MIN_INITIAL->value => [PriceDto::from(Money::of(290, $this->currency->value))],
            ProductPriceType::PRICE_MAX_INITIAL->value => [PriceDto::from(Money::of(350, $this->currency->value))],
            ProductPriceType::PRICE_MIN->value => [PriceDto::from(Money::of(290, $this->currency->value))],
            ProductPriceType::PRICE_MAX->value => [PriceDto::from(Money::of(350, $this->currency->value))],
        ]);

        $discount->products()->sync([$product2->getKey(), $product3->getKey()]);

        /** @var DiscountServiceContract $discountService */
        $discountService = App::make(DiscountServiceContract::class);

        // Apply discount to products before update
        $discountService->applyDiscountsOnProducts(Collection::make([$product1, $product2, $product3]));

        $data = [
            'active' => false,
            'target_products' => [
                $product2->getKey(),
                $product3->getKey(),
            ],
        ];

        unset($discountData['name'], $discountData['description'], $discountData['amounts']);
        $response = $this->actingAs($this->{$user})
            ->json('PATCH', '/sales/id:' . $discount->getKey(), $discountData + $data)
            ->assertOk();

        $response
            ->assertJsonFragment($discountData + ['id' => $discount->getKey()])
            ->assertJsonFragment([
                'id' => $product2->getKey(),
                'prices_base' => [
                    [
                        'currency' => $this->currency->value,
                        'gross' => '200.00',
                        'net' => '200.00',
                    ],
                ],
                'prices_min_initial' => [
                    [
                        'currency' => $this->currency->value,
                        'gross' => '190.00',
                        'net' => '190.00',
                    ],
                ],
                'prices_max_initial' => [
                    [
                        'currency' => $this->currency->value,
                        'gross' => '250.00',
                        'net' => '250.00',
                    ],
                ],
                'prices_min' => [
                    [
                        'currency' => $this->currency->value,
                        'gross' => '190.00',
                        'net' => '190.00',
                    ],
                ],
                'prices_max' => [
                    [
                        'currency' => $this->currency->value,
                        'gross' => '250.00',
                        'net' => '250.00',
                    ],
                ],
            ])
            ->assertJsonFragment([
                'id' => $product3->getKey(),
                'prices_base' => [
                    [
                        'currency' => $this->currency->value,
                        'gross' => '300.00',
                        'net' => '300.00',
                    ],
                ],
                'prices_min_initial' => [
                    [
                        'currency' => $this->currency->value,
                        'gross' => '290.00',
                        'net' => '290.00',
                    ],
                ],
                'prices_max_initial' => [
                    [
                        'currency' => $this->currency->value,
                        'gross' => '350.00',
                        'net' => '350.00',
                    ],
                ],
                'prices_min' => [
                    [
                        'currency' => $this->currency->value,
                        'gross' => '290.00',
                        'net' => '290.00',
                    ],
                ],
                'prices_max' => [
                    [
                        'currency' => $this->currency->value,
                        'gross' => '350.00',
                        'net' => '350.00',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('discounts', $discountData + ['id' => $discount->getKey()]);

        $this->assertDatabaseMissing('product_sales', [
            'product_id' => $product1->getKey(),
            'sale_id' => $discount->getKey(),
        ]);

        $this->assertProductPrices($product1->getKey(), [
            ProductPriceType::PRICE_BASE->value => 100,
            ProductPriceType::PRICE_MIN_INITIAL->value => 100,
            ProductPriceType::PRICE_MAX_INITIAL->value => 150,
            ProductPriceType::PRICE_MIN->value => 100,
            ProductPriceType::PRICE_MAX->value => 150,
        ]);

        $this->assertProductPrices($product2->getKey(), [
            ProductPriceType::PRICE_BASE->value => 200,
            ProductPriceType::PRICE_MIN_INITIAL->value => 190,
            ProductPriceType::PRICE_MAX_INITIAL->value => 250,
            ProductPriceType::PRICE_MIN->value => 190,
            ProductPriceType::PRICE_MAX->value => 250,
        ]);

        $this->assertProductPrices($product3->getKey(), [
            ProductPriceType::PRICE_BASE->value => 300,
            ProductPriceType::PRICE_MIN_INITIAL->value => 290,
            ProductPriceType::PRICE_MAX_INITIAL->value => 350,
            ProductPriceType::PRICE_MIN->value => 290,
            ProductPriceType::PRICE_MAX->value => 350,
        ]);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testUpdateExistingSlug(string $user, string $discountKind): void
    {
        $this->{$user}->givePermissionTo("{$discountKind}.edit");

        Discount::factory()->create(['slug' => 'existing-slug']);
        $code = $discountKind === 'coupons' ? ['code' => 'S43SA2'] : [];
        $discount = Discount::factory()->create($code);

        $this
            ->actingAs($this->{$user})
            ->json('PATCH', "/{$discountKind}/id:{$discount->getKey()}", [
                'slug' => 'existing-slug',
            ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'key' => ValidationError::UNIQUE,
                'message' => 'The slug has already been taken.'
            ]);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testUpdateExistingSlugDeleted(string $user, string $discountKind): void
    {
        $this->{$user}->givePermissionTo("{$discountKind}.edit");

        $existing = Discount::factory()->create(['slug' => 'existing-slug']);

        $code = $discountKind === 'coupons' ? ['code' => 'S43SA2'] : ['code' => null];
        $discount = Discount::factory()->create($code);

        $existing->delete();

        $this
            ->actingAs($this->{$user})
            ->json('PATCH', "/{$discountKind}/id:{$discount->getKey()}", [
                'slug' => 'existing-slug',
            ])
            ->assertOk()
            ->assertJsonFragment([
                'slug' => 'existing-slug',
            ]);
    }

    /**
     * @dataProvider authProvider
     *
     * @throws DtoException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     */
    public function testCreateActiveSaleAndExpiredAfter($user): void
    {
        Carbon::setTestNow('2022-05-12T12:00:00'); // Thursday
        $this->{$user}->givePermissionTo('sales.add');

        $product = Product::factory()->create([
            'public' => true,
        ]);
        $this->productRepository->setProductPrices($product->getKey(), [
            ProductPriceType::PRICE_BASE->value => [PriceDto::from(Money::of(1000, $this->currency->value))],
            ProductPriceType::PRICE_MIN_INITIAL->value => [PriceDto::from(Money::of(1000, $this->currency->value))],
            ProductPriceType::PRICE_MAX_INITIAL->value => [PriceDto::from(Money::of(1000, $this->currency->value))],
        ]);

        $discount = [
            'translations' => [
                $this->lang => [
                    'name' => 'Sale',
                    'description' => 'Test sale',
                ],
            ],
            'percentage' => '10.0000',
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

        $response = $this->actingAs($this->{$user})->json('POST', 'sales', $discount + $conditions);

        $response->assertCreated();

        $this->assertProductPrices($product->getKey(), [
            ProductPriceType::PRICE_MIN->value => 900,
            ProductPriceType::PRICE_MAX->value => 900,
        ]);

        $discountModel = Discount::find($response->getData()->data->id);

        $activeSales = Cache::get('sales.active');
        $this->assertCount(1, $activeSales);
        $this->assertTrue($activeSales->contains($discountModel->getKey()));

        Carbon::setTestNow('2022-05-12T19:00:00');
        $this->travelTo('2022-05-12T19:00:00');
        $this->artisan('schedule:run');

        $this->assertProductPrices($product->getKey(), [
            ProductPriceType::PRICE_MIN->value => 1000,
            ProductPriceType::PRICE_MAX->value => 1000,
        ]);

        $activeSales = Cache::get('sales.active');
        $this->assertCount(0, $activeSales);
        $this->assertFalse($activeSales->contains($discountModel->getKey()));
    }

    private function assertProductPrices(string $productId, array $priceMatrix): void
    {
        foreach ($priceMatrix as $type => $value) {
            $this->assertDatabaseHas('prices', [
                'model_id' => $productId,
                'price_type' => $type,
                'value' => $value * 100,
            ]);
        }
    }
}
