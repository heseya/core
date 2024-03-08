<?php

namespace Tests\Feature\Discounts;

use App\Enums\ConditionType;
use App\Enums\DiscountTargetType;
use App\Enums\DiscountType;
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
use App\Models\WebHook;
use App\Services\Contracts\DiscountServiceContract;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\Price\Dtos\PriceDto;
use Domain\Price\Enums\DiscountConditionPriceType;
use Domain\Price\Enums\ProductPriceType;
use Heseya\Dto\DtoException;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Spatie\WebhookServer\CallWebhookJob;

class DiscountUpdateTest extends DiscountTestCase
{
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
        $discount = Discount::factory(['target_type' => DiscountTargetType::ORDER_VALUE] + $code)->create();

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
            'min_values' => [
                [
                    'currency' => Currency::PLN->value,
                    'value' => "100.00",
                ],
                [
                    'currency' => Currency::GBP->value,
                    'value' => "25.00",
                ],
            ],
            'max_values' => [
                [
                    'currency' => Currency::PLN->value,
                    'value' => "500.00",
                ],
                [
                    'currency' => Currency::GBP->value,
                    'value' => "125.00",
                ],
            ],
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
                            'max_values' => [
                                [
                                    'currency' => Currency::PLN->value,
                                    'value' => "500.00",
                                ],
                                [
                                    'currency' => Currency::GBP->value,
                                    'value' => "125.00",
                                ],
                            ],
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
                'amounts' => Arr::map(Currency::values(), fn (string $currency) => [
                    'value' => '50.00',
                    'currency' => $currency,
                ]),
            ]);

        $code = $discountKind === 'coupons' ? ['code' => $discount->code] : [];

        $response
            ->assertOk()
            ->assertJsonFragment(
                [
                    'id' => $discount->getKey(),
                    'amounts' => [
                        [
                            'currency' => Currency::GBP->value,
                            'net' => '50.00',
                            'gross' => '50.00',
                        ],
                        [
                            'currency' => $this->currency->value,
                            'net' => '50.00',
                            'gross' => '50.00',
                        ]
                    ],
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
            'amounts' => [
                [
                    'currency' => Currency::GBP->value,
                    'value' => '10.00',
                ],
                [
                    'currency' => $this->currency->value,
                    'value' => '10.00',
                ],
            ],
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
                    'amounts' => [
                        [
                            'currency' => Currency::GBP->value,
                            'gross' => '10.00',
                            'net' => '10.00',
                        ],
                        [
                            'currency' => $this->currency->value,
                            'gross' => '10.00',
                            'net' => '10.00',
                        ],
                    ],
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
}
