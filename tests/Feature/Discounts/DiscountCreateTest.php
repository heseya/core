<?php

namespace Tests\Feature\Discounts;

use App\Enums\ConditionType;
use App\Enums\DiscountTargetType;
use App\Enums\DiscountType;
use App\Enums\ValidationError;
use App\Events\CouponCreated;
use App\Events\ProductPriceUpdated;
use App\Events\SaleCreated;
use App\Listeners\WebHookEventListener;
use App\Models\Discount;
use App\Models\Product;
use App\Models\WebHook;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\Price\Dtos\PriceDto;
use Domain\Price\Enums\ProductPriceType;
use Domain\ProductSet\ProductSet;
use Domain\ShippingMethod\Models\ShippingMethod;
use Heseya\Dto\DtoException;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Spatie\WebhookServer\CallWebhookJob;

class DiscountCreateTest extends DiscountTestCase
{
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
                'min_values' => [
                    [
                        'currency' => 'PLN',
                        'gross' => '100.00',
                        'net' => '100.00',
                    ],
                    [
                        'currency' => 'GBP',
                        'gross' => '25.00',
                        'net' => '25.00',
                    ],
                ],
                'max_values' => [
                    [
                        'currency' => 'PLN',
                        'gross' => '500.00',
                        'net' => '500.00',
                    ],
                    [
                        'currency' => 'GBP',
                        'gross' => '125.00',
                        'net' => '125.00',
                    ],
                ],
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
}
