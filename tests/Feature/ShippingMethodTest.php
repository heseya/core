<?php

namespace Tests\Feature;

use App\Enums\ShippingType;
use App\Enums\ValidationError;
use App\Models\Address;
use App\Models\App;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PriceRange;
use App\Models\Product;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\ProductSet\ProductSet;
use Domain\SalesChannel\Models\SalesChannel;
use Domain\ShippingMethod\Models\ShippingMethod;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ShippingMethodTest extends TestCase
{
    use WithFaker;

    public ShippingMethod $shipping_method;
    public ShippingMethod $shipping_method_hidden;
    public array $expected;
    public array $priceRanges;
    public array $priceRangesWithNoInitialStart;

    /**
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->shipping_method = ShippingMethod::factory()->create([
            'public' => true,
            'is_block_list_countries' => true,
            'shipping_time_min' => 1,
            'shipping_time_max' => 2,
        ]);

        $currency = Currency::DEFAULT->value;
        $lowRange = PriceRange::query()->create([
            'start' => Money::zero($currency),
            'value' => Money::of(mt_rand(8, 15) + (mt_rand(0, 99) / 100), $currency),
        ]);

        $highRange = PriceRange::query()->create([
            'start' => Money::of(210, $currency),
            'value' => Money::zero($currency),
        ]);

        $this->shipping_method->priceRanges()->saveMany([$lowRange, $highRange]);

        $this->shipping_method_hidden = ShippingMethod::factory()->create([
            'public' => false,
            'is_block_list_countries' => true,
        ]);

        $lowRange = PriceRange::query()->create([
            'start' => Money::zero($currency),
            'value' => Money::of(mt_rand(8, 15) + (mt_rand(0, 99) / 100), $currency),
        ]);

        $highRange = PriceRange::query()->create([
            'start' => Money::of(210, $currency),
            'value' => Money::zero($currency),
        ]);

        $this->shipping_method_hidden->priceRanges()->saveMany([$lowRange, $highRange]);

        // Expected response
        $this->expected = [
            'id' => $this->shipping_method->getKey(),
            'name' => $this->shipping_method->name,
            'public' => $this->shipping_method->public,
            'shipping_time_min' => $this->shipping_method->shipping_time_min,
            'shipping_time_max' => $this->shipping_method->shipping_time_max,
            'metadata' => [],
        ];

        $this->priceRangesWithNoInitialStart = [];
        foreach (Currency::cases() as $currency) {
            $this->priceRangesWithNoInitialStart[] = [
                'currency' => $currency->value,
                'start' => '' . $this->faker()->randomFloat(2, 1, 49),
                'value' => '' . $this->faker()->randomFloat(2, 20),
            ];
        }

        $this->priceRanges = [];
        foreach (Currency::cases() as $currency) {
            $this->priceRanges[] = [
                'currency' => $currency->value,
                'start' => '0',
                'value' => '10.37',
            ];

            $this->priceRanges[] = [
                'currency' => $currency->value,
                'start' => '200',
                'value' => '0',
            ];
        }
    }

    public function testIndexUnauthorized(): void
    {
        $this->getJson('/shipping-methods')->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndex($user): void
    {
        $this->{$user}->givePermissionTo('shipping_methods.show');

        $response = $this->actingAs($this->{$user})->getJson('/shipping-methods');
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data') // Should show only public shipping methods.
            ->assertJson([
                'data' => [
                    0 => $this->expected,
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexBlocklistProductsAndSets($user): void
    {
        $this->{$user}->givePermissionTo('shipping_methods.show');

        ShippingMethod::query()->delete();

        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        $product3 = Product::factory()->create();
        $product4 = Product::factory()->create();
        $product5 = Product::factory()->create();

        $productSet1 = ProductSet::factory()->create();
        $productForSet1 = Product::factory()->create();
        $productForSet2 = Product::factory()->create();
        $productSet1->products()->sync([$productForSet1->getKey(), $productForSet2->getKey()]);

        $productSet2 = ProductSet::factory()->create();
        $productForSet3 = Product::factory()->create();
        $productForSet4 = Product::factory()->create();
        $productSet2->products()->sync([$productForSet3->getKey(), $productForSet4->getKey()]);

        $productSet3 = ProductSet::factory()->create();
        $productForSet5 = Product::factory()->create();
        $productForSet6 = Product::factory()->create();
        $productSet3->products()->sync([$productForSet5->getKey(), $productForSet6->getKey()]);

        $shippingMethodAllowList = ShippingMethod::factory()->create([
            'name' => 'Test Allow-list',
            'is_block_list_products' => false,
            'public' => true,
        ]);

        $shippingMethodAllowList->products()->sync([
            $product1->getKey(),
            $product2->getKey(),
            $product5->getKey(),
        ]);

        $shippingMethodAllowList->productSets()->sync([
            $productSet1->getKey(),
            $productSet2->getKey(),
            $productSet3->getKey(),
        ]);

        $shippingMethodBlocklist = ShippingMethod::factory()->create([
            'name' => 'Test Block-list',
            'is_block_list_products' => true,
            'public' => true,
        ]);

        $shippingMethodBlocklist->products()->sync([
            $product4->getKey(),
            $product5->getKey(),
        ]);

        $shippingMethodBlocklist->productSets()->sync([
            $productSet1->getKey(),
            $productSet2->getKey(),
        ]);

        $this->actingAs($this->{$user})->json('GET', '/shipping-methods', [
            'items' => [
                $product1->getKey(),
                $product5->getKey(),
                $productForSet1->getKey(),
                $productForSet2->getKey(),
            ],
        ])
            ->assertJsonCount(1, 'data')
            ->assertJsonCount(3, 'data.0.product_ids')
            ->assertJsonCount(3, 'data.0.product_set_ids')
            ->assertJsonFragment([
                'id' => $shippingMethodAllowList->getKey(),
            ]);

        $this->actingAs($this->{$user})->json('GET', '/shipping-methods', [
            'items' => [
                $product1->getKey(),
                $product5->getKey(),
                $productForSet1->getKey(),
                $productForSet2->getKey(),
                $product3->getKey(),
            ],
        ])
            ->assertJsonCount(0, 'data');

        $this->actingAs($this->{$user})->json('GET', '/shipping-methods', [
            'items' => [
                $product3->getKey(),
                $productForSet5->getKey(),
                $productForSet6->getKey(),
            ],
        ])
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $shippingMethodBlocklist->getKey(),
            ]);

        $this->actingAs($this->{$user})->json('GET', '/shipping-methods', [
            'items' => [$product1->getKey(), $product2->getKey()],
        ])
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'id' => $shippingMethodAllowList->getKey(),
            ])
            ->assertJsonFragment([
                'id' => $shippingMethodBlocklist->getKey(),
            ]);

        $this->actingAs($this->{$user})->json('GET', '/shipping-methods', [
            'items' => [$product1->getKey(), $product3->getKey()],
        ])
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $shippingMethodBlocklist->getKey(),
            ]);

        $this->actingAs($this->{$user})->json('GET', '/shipping-methods', [
            'items' => [$product5->getKey()],
        ])
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $shippingMethodAllowList->getKey(),
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexBySalesChannels($user): void
    {
        $this->{$user}->givePermissionTo('shipping_methods.show');

        ShippingMethod::query()->delete();

        $shippingMethod1 = ShippingMethod::factory()->create([
            'public' => true,
        ]);

        $shippingMethod2 = ShippingMethod::factory()->create([
            'public' => true,
        ]);

        $salesChannel1 = SalesChannel::factory()->create();
        $salesChannel2 = SalesChannel::factory()->create();

        $shippingMethod1->salesChannels()->sync([$salesChannel1->getKey()]);
        $shippingMethod2->salesChannels()->sync([$salesChannel1->getKey(), $salesChannel2->getKey()]);

        $response = $this->actingAs($this->{$user})->getJson(
            '/shipping-methods?sales_channel_id=' . $salesChannel1->getKey()
        );

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'id' => $shippingMethod1->getKey(),
            ])
            ->assertJsonFragment([
                'id' => $shippingMethod2->getKey(),
            ]);

        $response = $this->actingAs($this->{$user})->getJson(
            '/shipping-methods?sales_channel_id=' . $salesChannel2->getKey()
        );

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonCount(2, 'data.0.sales_channels')
            ->assertJson([
                'data' => [
                    0 => [
                        'id' => $shippingMethod2->getKey(),
                    ],
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexByIds($user): void
    {
        $this->{$user}->givePermissionTo('shipping_methods.show');

        ShippingMethod::factory()->count(10)->create();

        $this->actingAs($this->{$user})
            ->json('GET', '/shipping-methods', [
                'ids' => [
                    $this->shipping_method->getKey(),
                ],
            ])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    0 => $this->expected,
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexHidden($user): void
    {
        $this->{$user}->givePermissionTo(['shipping_methods.show', 'shipping_methods.show_hidden']);

        $response = $this->actingAs($this->{$user})->getJson('/shipping-methods');
        $response
            ->assertOk()
            ->assertJsonCount(2, 'data') // Should show only public shipping methods.
            ->assertJsonFragment($this->expected)
            ->assertJsonFragment([
                'id' => $this->shipping_method_hidden->getKey(),
                'name' => $this->shipping_method_hidden->name,
                'public' => $this->shipping_method_hidden->public,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexWithPaymentMethods($user): void
    {
        $this->{$user}->givePermissionTo('shipping_methods.show');

        $paymentMethod = PaymentMethod::factory()->create([
            'public' => true,
        ]);

        $paymentMethodHidden = PaymentMethod::factory()->create([
            'public' => false,
        ]);

        $this->shipping_method->paymentMethods()->sync([
            $paymentMethod->getKey(),
            $paymentMethodHidden->getKey(),
        ]);

        $response = $this->actingAs($this->{$user})->getJson('/shipping-methods');
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data') // Should show only public shipping methods.
            ->assertJson([
                'data' => [
                    0 => $this->expected,
                ],
            ])
            ->assertJsonCount(1, 'data.0.payment_methods')
            ->assertJsonFragment(['id' => $paymentMethod->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexWithPaymentMethodsShowHidden($user): void
    {
        $this->{$user}->givePermissionTo(['shipping_methods.show', 'payment_methods.show_hidden']);

        $paymentMethod = PaymentMethod::factory()->create([
            'public' => true,
        ]);

        $paymentMethodHidden = PaymentMethod::factory()->create([
            'public' => false,
        ]);

        $this->shipping_method->paymentMethods()->sync([
            $paymentMethod->getKey(),
            $paymentMethodHidden->getKey(),
        ]);

        $response = $this->actingAs($this->{$user})->getJson('/shipping-methods');
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data') // Should show only public shipping methods.
            ->assertJson([
                'data' => [
                    0 => $this->expected,
                ],
            ])
            ->assertJsonCount(2, 'data.0.payment_methods')
            ->assertJsonFragment(['id' => $paymentMethod->getKey()])
            ->assertJsonFragment(['id' => $paymentMethodHidden->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexWithPaymentMethodsEdit($user): void
    {
        $this->{$user}->givePermissionTo(['shipping_methods.show', 'shipping_methods.edit']);

        $paymentMethod = PaymentMethod::factory()->create([
            'public' => true,
        ]);

        $paymentMethodHidden = PaymentMethod::factory()->create([
            'public' => false,
        ]);

        $this->shipping_method->paymentMethods()->sync([
            $paymentMethod->getKey(),
            $paymentMethodHidden->getKey(),
        ]);

        $response = $this->actingAs($this->{$user})->getJson('/shipping-methods');
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data') // Should show only public shipping methods.
            ->assertJson([
                'data' => [
                    0 => $this->expected,
                ],
            ])
            ->assertJsonCount(2, 'data.0.payment_methods')
            ->assertJsonFragment(['id' => $paymentMethod->getKey()])
            ->assertJsonFragment(['id' => $paymentMethodHidden->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexByCountry($user): void
    {
        $this->{$user}->givePermissionTo('shipping_methods.show');

        // All countries without Germany
        $shippingMethod = ShippingMethod::factory()->create([
            'public' => true,
            'is_block_list_countries' => true,
        ]);
        $shippingMethod->countries()->sync(['DE']);

        // Only Germany
        $shippingMethod2 = ShippingMethod::factory()->create([
            'public' => true,
            'is_block_list_countries' => false,
        ]);
        $shippingMethod2->countries()->sync(['DE']);

        $response = $this->actingAs($this->{$user})
            ->json('GET', '/shipping-methods', ['country' => 'DE']);
        $response
            ->assertOk()
            ->assertJsonCount(2, 'data') // Should show only public shipping methods.
            ->assertJsonFragment(['id' => $this->shipping_method->getKey()])
            ->assertJsonFragment(['id' => $shippingMethod2->getKey()]);
    }

    /**
     * Price range testing with no initial 'start' value of zero.
     *
     * @dataProvider authProvider
     */
    public function testCreateByPriceRanges($user): void
    {
        $this->{$user}->givePermissionTo('shipping_methods.add');

        $shipping_method = [
            'name' => 'Test 4',
            'public' => false,
            'shipping_type' => ShippingType::ADDRESS->value,
        ];

        $response = $this->actingAs($this->{$user})->postJson(
            '/shipping-methods',
            $shipping_method + [
                'price_ranges' => $this->priceRangesWithNoInitialStart,
            ],
        );

        $response->assertStatus(422);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateByPriceRangesStartZero($user): void
    {
        $this->{$user}->givePermissionTo('shipping_methods.add');

        $shipping_method = [
            'name' => 'Test 4',
            'public' => false,
            'shipping_type' => ShippingType::DIGITAL->value,
            'shipping_time_max' => 0,
            'shipping_time_min' => 0,
            'payment_on_delivery' => false,
        ];

        $response = $this->actingAs($this->{$user})->postJson(
            '/shipping-methods',
            $shipping_method + [
                'price_ranges' => [
                    [
                        'currency' => Currency::GBP->value,
                        'start' => '0',
                        'value' => '0',
                    ],
                    [
                        'currency' => Currency::PLN->value,
                        'start' => '0.00',
                        'value' => '16.61',
                    ],
                ],
            ],
        );

        $response
            ->assertCreated()
            ->assertJsonFragment([
                'start' => [
                    'gross' => '0.00',
                    'currency' => Currency::GBP->value,
                ],
            ])
            ->assertJsonFragment([
                'value' => [
                    'gross' => '0.00',
                    'currency' => Currency::GBP->value,
                ],
            ])
            ->assertJsonFragment([
                'start' => [
                    'gross' => '0.00',
                    'currency' => Currency::PLN->value,
                ],
            ])
            ->assertJsonFragment([
                'value' => [
                    'gross' => '16.61',
                    'currency' => Currency::PLN->value,
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateByPriceRangesAsNumbers($user): void
    {
        $this->{$user}->givePermissionTo('shipping_methods.add');

        $shipping_method = [
            'name' => 'Test 4',
            'public' => false,
            'shipping_type' => ShippingType::DIGITAL->value,
            'shipping_time_max' => 0,
            'shipping_time_min' => 0,
        ];

        $this->actingAs($this->{$user})->postJson(
            '/shipping-methods',
            $shipping_method + [
                'price_ranges' => [
                    [
                        'currency' => Currency::DEFAULT->value,
                        'start' => 0.0,
                        'value' => 0.0,
                    ],
                ],
            ],
        )
            ->assertUnprocessable()
            ->assertJsonFragment([
                'key' => ValidationError::PRICE,
                'message' => 'The price_ranges.0 value must be a decimal string, integer found',
            ]);
    }

    /**
     * Price range testing with duplicate "start" values.
     *
     * @dataProvider authProvider
     */
    public function testCreateByDuplicatePriceRanges($user): void
    {
        $this->{$user}->givePermissionTo('shipping_methods.add');

        $shipping_method = [
            'name' => 'Test 5',
            'public' => false,
            'shipping_time_min' => 2,
            'shipping_time_max' => 3,
            'shipping_type' => ShippingType::ADDRESS->value,
        ];

        $price_ranges = [];
        foreach (Currency::cases() as $currency) {
            $price_ranges[] = [
                'currency' => $currency->value,
                'start' => '0',
                'value' => '0',
            ];

            $price_ranges[] = [
                'currency' => $currency->value,
                'start' => '0',
                'value' => '10',
            ];
        }

        $response = $this->actingAs($this->{$user})->postJson(
            '/shipping-methods',
            $shipping_method + [
                'price_ranges' => $price_ranges,
            ],
        );

        $response->assertStatus(422);
    }

    public function testCreateUnauthorized(): void
    {
        $response = $this->postJson('/shipping-methods');
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreate($user): void
    {
        $this->{$user}->givePermissionTo('shipping_methods.add');

        ShippingMethod::query()->delete();

        $shipping_method = [
            'name' => 'Test',
            'public' => true,
            'is_block_list_countries' => false,
            'shipping_time_min' => 2,
            'shipping_time_max' => 3,
            'shipping_type' => ShippingType::ADDRESS->value,
            'payment_on_delivery' => true,
        ];

        $response = $this->actingAs($this->{$user})
            ->postJson(
                '/shipping-methods',
                $shipping_method + [
                    'price_ranges' => $this->priceRanges,
                ]
            );

        $response
            ->assertCreated()
            ->assertJson(['data' => $shipping_method])
            ->assertJsonFragment([
                'start' => [
                    'gross' => '0.00',
                    'currency' => Currency::DEFAULT->value,
                ],
            ])
            ->assertJsonFragment([
                'value' => [
                    'gross' => '10.37',
                    'currency' => Currency::DEFAULT->value,
                ],
            ])
            ->assertJsonFragment([
                'start' => [
                    'gross' => '200.00',
                    'currency' => Currency::DEFAULT->value,
                ],
            ])
            ->assertJsonFragment([
                'value' => [
                    'gross' => '0.00',
                    'currency' => Currency::DEFAULT->value,
                ],
            ]);

        $this->assertDatabaseHas('shipping_methods', $shipping_method);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithSalesChannels($user): void
    {
        $this->{$user}->givePermissionTo('shipping_methods.add');

        ShippingMethod::query()->delete();

        $shipping_method = [
            'name' => 'Test',
            'public' => true,
            'is_block_list_countries' => false,
            'shipping_time_min' => 2,
            'shipping_time_max' => 3,
            'shipping_type' => ShippingType::ADDRESS->value,
            'payment_on_delivery' => true,
        ];

        $salesChannel1 = SalesChannel::factory()->create();
        $salesChannel2 = SalesChannel::factory()->create();

        $response = $this->actingAs($this->{$user})
            ->postJson(
                '/shipping-methods',
                $shipping_method + [
                    'price_ranges' => $this->priceRanges,
                    'sales_channels' => [
                        $salesChannel1->getKey(),
                        $salesChannel2->getKey(),
                    ],
                ]
            );

        $response
            ->assertCreated()
            ->assertJson(['data' => $shipping_method])
            ->assertJsonFragment([
                'start' => [
                    'gross' => '0.00',
                    'currency' => Currency::DEFAULT->value,
                ],
            ])
            ->assertJsonFragment([
                'value' => [
                    'gross' => '10.37',
                    'currency' => Currency::DEFAULT->value,
                ],
            ])
            ->assertJsonFragment([
                'start' => [
                    'gross' => '200.00',
                    'currency' => Currency::DEFAULT->value,
                ],
            ])
            ->assertJsonFragment([
                'value' => [
                    'gross' => '0.00',
                    'currency' => Currency::DEFAULT->value,
                ],
            ]);

        $this->assertDatabaseHas('shipping_methods', $shipping_method);
        $this->assertDatabaseHas('sales_channel_shipping_method', [
            'shipping_method_id' => $response->json('data.id'),
            'sales_channel_id' => $salesChannel1->getKey(),
        ]);
        $this->assertDatabaseHas('sales_channel_shipping_method', [
            'shipping_method_id' => $response->json('data.id'),
            'sales_channel_id' => $salesChannel2->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithBlocklist($user): void
    {
        $this->{$user}->givePermissionTo('shipping_methods.add');

        ShippingMethod::query()->delete();

        $product = Product::factory()->create();
        $productSet = ProductSet::factory()->create();

        $shipping_method = [
            'name' => 'Test',
            'public' => true,
            'is_block_list_countries' => false,
            'shipping_time_min' => 2,
            'shipping_time_max' => 3,
            'shipping_type' => ShippingType::ADDRESS->value,
            'payment_on_delivery' => true,
            'is_block_list_products' => true,
            'product_ids' => [$product->getKey()],
            'product_set_ids' => [$productSet->getKey()],
        ];

        $response = $this->actingAs($this->{$user})
            ->postJson(
                '/shipping-methods',
                $shipping_method + [
                    'price_ranges' => $this->priceRanges,
                ]
            );

        $response
            ->assertCreated()
            ->assertJsonFragment(['is_block_list_products' => true]);

        $shippingMethod = ShippingMethod::find($response->json('data.id'));

        $this->assertTrue($shippingMethod->products()->count() === 1);
        $this->assertTrue($shippingMethod->productSets()->count() === 1);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithMetadata($user): void
    {
        $this->{$user}->givePermissionTo('shipping_methods.add');

        ShippingMethod::query()->delete();

        $shipping_method = [
            'name' => 'Test',
            'public' => true,
            'is_block_list_countries' => false,
            'shipping_time_min' => 2,
            'shipping_time_max' => 3,
            'shipping_type' => ShippingType::ADDRESS->value,
            'payment_on_delivery' => false,
            'metadata' => [
                'attributeMeta' => 'attributeValue',
            ],
        ];

        $this
            ->actingAs($this->{$user})
            ->postJson(
                '/shipping-methods',
                $shipping_method + [
                    'price_ranges' => $this->priceRanges,
                ]
            )
            ->assertCreated()
            ->assertJson(['data' => $shipping_method]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithMetadataPrivate($user): void
    {
        $this->{$user}->givePermissionTo(['shipping_methods.add', 'shipping_methods.show_metadata_private']);

        ShippingMethod::query()->delete();

        $shipping_method = [
            'name' => 'Test',
            'public' => true,
            'is_block_list_countries' => false,
            'shipping_time_min' => 2,
            'shipping_time_max' => 3,
            'shipping_type' => ShippingType::ADDRESS->value,
            'payment_on_delivery' => true,
            'metadata_private' => [
                'attributeMetaPriv' => 'attributeValue',
            ],
        ];

        $this
            ->actingAs($this->{$user})
            ->postJson(
                '/shipping-methods',
                $shipping_method + [
                    'price_ranges' => $this->priceRanges,
                ]
            )
            ->assertCreated()
            ->assertJson(['data' => $shipping_method]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateShippingTime($user): void
    {
        $this->{$user}->givePermissionTo('shipping_methods.add');

        ShippingMethod::query()->delete();

        $shipping_method = [
            'name' => 'Test Shipping Time',
            'public' => true,
            'is_block_list_countries' => false,
            'shipping_time_min' => 2,
            'shipping_time_max' => 3,
            'shipping_type' => ShippingType::ADDRESS->value,
            'payment_on_delivery' => true,
        ];

        $response = $this->actingAs($this->{$user})
            ->json(
                'POST',
                '/shipping-methods',
                $shipping_method + [
                    'price_ranges' => $this->priceRanges,
                ]
            );

        $response
            ->assertCreated()
            ->assertJson(['data' => $shipping_method]);

        $this->assertDatabaseHas('shipping_methods', $shipping_method);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateShippingTimeMaxLessThanMin($user): void
    {
        $this->{$user}->givePermissionTo('shipping_methods.add');

        ShippingMethod::query()->delete();

        $response = $this->actingAs($this->{$user})
            ->json('POST', '/shipping-methods', [
                'name' => 'Test Shipping Time',
                'public' => true,
                'is_block_list_countries' => false,
                'shipping_time_min' => 3,
                'shipping_time_max' => 2,
                'shipping_type' => ShippingType::ADDRESS->value,
                'price_ranges' => $this->priceRanges,
                'payment_on_delivery' => false,
            ]);

        $response->assertStatus(422);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateBlockList($user): void
    {
        $this->{$user}->givePermissionTo('shipping_methods.add');

        $shipping_method = [
            'name' => 'Test',
            'public' => true,
            'is_block_list_countries' => true,
            'shipping_time_min' => 2,
            'shipping_time_max' => 2,
            'shipping_type' => ShippingType::ADDRESS->value,
            'payment_on_delivery' => false,
        ];

        $response = $this->actingAs($this->{$user})
            ->postJson(
                '/shipping-methods',
                $shipping_method + [
                    'price_ranges' => $this->priceRanges,
                ]
            );

        $response
            ->assertCreated()
            ->assertJson(['data' => $shipping_method]);

        $this->assertDatabaseHas('shipping_methods', $shipping_method);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithShippingPoints($user): void
    {
        $this->{$user}->givePermissionTo('shipping_methods.add');

        ShippingMethod::query()->delete();
        Address::query()->delete();

        $address = Address::factory()->create();

        $shipping_method = [
            'name' => 'Test',
            'public' => true,
            'is_block_list_countries' => false,
            'shipping_time_min' => 2,
            'shipping_time_max' => 3,
            'shipping_type' => ShippingType::POINT->value,
            'payment_on_delivery' => false,
        ];
        $shipping_points = [
            'shipping_points' => [
                [
                    'id' => $address->getKey(),
                    'name' => 'test1',
                ],
                [
                    'name' => 'test2',
                ],
            ],
        ];

        $response = $this->actingAs($this->{$user})
            ->postJson(
                '/shipping-methods',
                $shipping_method + [
                    'price_ranges' => $this->priceRanges,
                ] + $shipping_points
            );

        $addressSaved = Address::where('name', 'test2')->first();

        $response
            ->assertCreated()
            ->assertJson(['data' => $shipping_method])
            ->assertJsonCount(2, 'data.shipping_points')
            ->assertJsonFragment(['shipping_type' => ShippingType::POINT->value]);

        $this
            ->assertDatabaseHas(
                'shipping_methods',
                $shipping_method + [
                    'app_id' => $this->{$user} instanceof App ? $this->{$user}->getKey() : null,
                ]
            )
            ->assertDatabaseHas('address_shipping_method', [
                'address_id' => $address->getKey(),
                'shipping_method_id' => $response->getData()->data->id,
            ])
            ->assertDatabaseHas('address_shipping_method', [
                'address_id' => $addressSaved->getKey(),
                'shipping_method_id' => $response->getData()->data->id,
            ]);
    }

    /**
     * Price range testing with no initial 'start' value of zero.
     *
     * @dataProvider authProvider
     */
    public function testUpdateByPriceRanges($user): void
    {
        $this->{$user}->givePermissionTo('shipping_methods.edit');

        $shipping_method = [
            'name' => 'Test 6',
            'public' => false,
        ];

        $response = $this->actingAs($this->{$user})->patchJson(
            '/shipping-methods/id:' . $this->shipping_method->getKey(),
            $shipping_method + [
                'price_ranges' => $this->priceRangesWithNoInitialStart,
            ],
        );

        $response->assertStatus(422);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateShippingMethodShippingPoints($user): void
    {
        $this->{$user}->givePermissionTo('shipping_methods.edit');

        ShippingMethod::query()->delete();
        Address::query()->delete();

        $shippingMethod = ShippingMethod::factory()->create();

        $address = Address::factory()->create();
        $shippingMethod->shippingPoints()->sync($address);

        $response = $this->actingAs($this->{$user})
            ->patchJson('/shipping-methods/id:' . $shippingMethod->getKey(), [
                'name' => 'test',
                'shipping_type' => ShippingType::ADDRESS->value,
                'shipping_points' => [
                    [
                        'id' => $address->getKey(),
                        'name' => 'test1',
                    ],
                    [
                        'name' => 'test2',
                    ],
                ],
            ]);

        $addressSaved = Address::where('name', 'test2')->first();

        $response
            ->assertOk()
            ->assertJsonFragment(['shipping_type' => ShippingType::ADDRESS->value])
            ->assertJsonCount(2, 'data.shipping_points');

        $this
            ->assertDatabaseHas('address_shipping_method', [
                'address_id' => $address->getKey(),
                'shipping_method_id' => $response->getData()->data->id,
            ])
            ->assertDatabaseHas('address_shipping_method', [
                'address_id' => $addressSaved->getKey(),
                'shipping_method_id' => $response->getData()->data->id,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithSalesChannels($user): void
    {
        $this->{$user}->givePermissionTo('shipping_methods.edit');

        $shippingMethod = ShippingMethod::factory()->create();

        $salesChannel1 = SalesChannel::factory()->create();
        $salesChannel2 = SalesChannel::factory()->create();

        $shippingMethod->salesChannels()->sync([
            $salesChannel1->getKey(),
            $salesChannel2->getKey(),
        ]);

        $newSalesChannel = SalesChannel::factory()->create();

        $response = $this->actingAs($this->{$user})
            ->patchJson('/shipping-methods/id:' . $shippingMethod->getKey(), [
                'sales_channels' => [
                    $newSalesChannel->getKey(),
                ],
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('sales_channel_shipping_method', [
            'shipping_method_id' => $response->json('data.id'),
            'sales_channel_id' => $newSalesChannel->getKey(),
        ]);
        $this->assertDatabaseMissing('sales_channel_shipping_method', [
            'shipping_method_id' => $response->json('data.id'),
            'sales_channel_id' => $salesChannel1->getKey(),
        ]);
        $this->assertDatabaseMissing('sales_channel_shipping_method', [
            'shipping_method_id' => $response->json('data.id'),
            'sales_channel_id' => $salesChannel2->getKey(),
        ]);
    }

    /**
     * Price range testing with duplicate "start" values.
     *
     * @dataProvider authProvider
     */
    public function testUpdateByDuplicatePriceRanges($user): void
    {
        $this->{$user}->givePermissionTo('shipping_methods.edit');

        $shipping_method = [
            'name' => 'Test 7',
            'public' => false,
        ];

        $price_ranges = [];
        foreach (Currency::cases() as $currency) {
            $price_ranges[] = [
                'currency' => $currency->value,
                'start' => '0',
                'value' => '0',
            ];

            $price_ranges[] = [
                'currency' => $currency->value,
                'start' => '0',
                'value' => '10',
            ];
        }

        $response = $this->actingAs($this->{$user})->patchJson(
            '/shipping-methods/id:' . $this->shipping_method->getKey(),
            $shipping_method + [
                'price_ranges' => $price_ranges,
            ],
        );

        $response->assertStatus(422);
    }

    public function testUpdateUnauthorized(): void
    {
        $this->patchJson('/shipping-methods/id:' . $this->shipping_method->getKey())
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithBlocklist($user): void
    {
        $this->{$user}->givePermissionTo('shipping_methods.edit');

        $this->shipping_method->update(['is_block_list_products' => false]);

        $product = Product::factory()->create();
        $productSet = ProductSet::factory()->create();

        $response = $this->actingAs($this->{$user})->patchJson(
            '/shipping-methods/id:' . $this->shipping_method->getKey(),
            [
                'price_ranges' => $this->priceRanges,
                'is_block_list_products' => true,
                'product_ids' => [$product->getKey()],
                'product_set_ids' => [$productSet->getKey()],
            ],
        );

        $response
            ->assertOk()
            ->assertJsonFragment(['is_block_list_products' => true]);

        $this->assertTrue($this->shipping_method->products()->count() === 1);
        $this->assertTrue($this->shipping_method->productSets()->count() === 1);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdate($user): void
    {
        $this->{$user}->givePermissionTo('shipping_methods.edit');

        $shipping_method = [
            'name' => 'Test 2',
            'public' => false,
            'is_block_list_countries' => false,
        ];

        $response = $this->actingAs($this->{$user})->patchJson(
            '/shipping-methods/id:' . $this->shipping_method->getKey(),
            $shipping_method + [
                'price_ranges' => $this->priceRanges,
            ],
        );

        $response
            ->assertOk()
            ->assertJson(['data' => $shipping_method])
            ->assertJsonFragment([
                'start' => [
                    'gross' => '0.00',
                    'currency' => Currency::DEFAULT->value,
                ],
            ])
            ->assertJsonFragment([
                'value' => [
                    'gross' => '10.37',
                    'currency' => Currency::DEFAULT->value,
                ],
            ])
            ->assertJsonFragment([
                'start' => [
                    'gross' => '200.00',
                    'currency' => Currency::DEFAULT->value,
                ],
            ])
            ->assertJsonFragment([
                'value' => [
                    'gross' => '0.00',
                    'currency' => Currency::DEFAULT->value,
                ],
            ]);

        $this->assertDatabaseHas(
            'shipping_methods',
            $shipping_method + ['id' => $this->shipping_method->getKey()],
        );
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateByPriceRangesStartZero($user): void
    {
        $this->{$user}->givePermissionTo('shipping_methods.edit');

        $shipping_method = [
            'name' => 'Test 2',
            'public' => false,
            'is_block_list_countries' => false,
        ];

        $response = $this->actingAs($this->{$user})->patchJson(
            '/shipping-methods/id:' . $this->shipping_method->getKey(),
            $shipping_method + [
                'price_ranges' => [
                    [
                        'currency' => Currency::GBP->value,
                        'start' => '0',
                        'value' => '0',
                    ],
                    [
                        'currency' => Currency::PLN->value,
                        'start' => '0.00',
                        'value' => '16.61',
                    ],
                ],
            ],
        );

        $response
            ->assertOk()
            ->assertJson(['data' => $shipping_method])
            ->assertJsonFragment([
                'start' => [
                    'gross' => '0.00',
                    'currency' => Currency::GBP->value,
                ],
            ])
            ->assertJsonFragment([
                'value' => [
                    'gross' => '0.00',
                    'currency' => Currency::GBP->value,
                ],
            ])
            ->assertJsonFragment([
                'start' => [
                    'gross' => '0.00',
                    'currency' => Currency::PLN->value,
                ],
            ])
            ->assertJsonFragment([
                'value' => [
                    'gross' => '16.61',
                    'currency' => Currency::PLN->value,
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithEmptyData($user): void
    {
        $this->{$user}->givePermissionTo('shipping_methods.edit');

        $response = $this->actingAs($this->{$user})->patchJson(
            '/shipping-methods/id:' . $this->shipping_method->getKey(),
            [],
        );

        $response
            ->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $this->shipping_method->getKey(),
                    'name' => $this->shipping_method->name,
                    'public' => $this->shipping_method->public,
                    'is_block_list_countries' => $this->shipping_method->is_block_list_countries,
                    'shipping_time_min' => $this->shipping_method->shipping_time_min,
                    'shipping_time_max' => $this->shipping_method->shipping_time_max,
                ],
            ])
            ->assertJsonFragment([
                'start' => [
                    'gross' => $this->shipping_method->priceRanges->first()->start->getAmount(),
                    'currency' => Currency::DEFAULT->value,
                ],
            ])
            ->assertJsonFragment([
                'value' => [
                    'gross' => $this->shipping_method->priceRanges->first()->value->getAmount(),
                    'currency' => Currency::DEFAULT->value,
                ],
            ])
            ->assertJsonFragment([
                'start' => [
                    'gross' => $this->shipping_method->priceRanges->last()->start->getAmount(),
                    'currency' => Currency::DEFAULT->value,
                ],
            ])
            ->assertJsonFragment([
                'value' => [
                    'gross' => $this->shipping_method->priceRanges->last()->value->getAmount(),
                    'currency' => Currency::DEFAULT->value,
                ],
            ]);

        $this->assertDatabaseHas('shipping_methods', [
            'id' => $this->shipping_method->getKey(),
            'name' => $this->shipping_method->name,
            'public' => $this->shipping_method->public,
            'is_block_list_countries' => $this->shipping_method->is_block_list_countries,
            'shipping_time_min' => $this->shipping_method->shipping_time_min,
            'shipping_time_max' => $this->shipping_method->shipping_time_max,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateBlacklistToTrue($user): void
    {
        $this->{$user}->givePermissionTo('shipping_methods.edit');

        $this->shipping_method->update(['is_block_list_countries' => false]);

        $shipping_method = [
            'name' => 'Test 2',
            'public' => false,
            'is_block_list_countries' => true,
        ];

        $response = $this->actingAs($this->{$user})->patchJson(
            '/shipping-methods/id:' . $this->shipping_method->getKey(),
            $shipping_method + [
                'price_ranges' => $this->priceRanges,
            ],
        );

        $response
            ->assertOk()
            ->assertJson(['data' => $shipping_method]);

        $this->assertDatabaseHas(
            'shipping_methods',
            $shipping_method + ['id' => $this->shipping_method->getKey()],
        );
    }

    public function testDeleteUnauthorized(): void
    {
        $response = $this->deleteJson('/shipping-methods/id:' . $this->shipping_method->getKey());
        $response->assertForbidden();
        $this->assertDatabaseHas('shipping_methods', $this->shipping_method->toArray());
    }

    /**
     * @dataProvider authProvider
     */
    public function testDelete($user): void
    {
        $this->{$user}->givePermissionTo('shipping_methods.remove');
        $response = $this->actingAs($this->{$user})
            ->deleteJson('/shipping-methods/id:' . $this->shipping_method->getKey());
        $response->assertNoContent();
        $this->assertModelMissing($this->shipping_method);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteOtherAppMethod($user): void
    {
        $app = App::factory()->create();
        $shippingMethod = ShippingMethod::factory()->create([
            'app_id' => $app->getKey(),
        ]);
        $this->{$user}->givePermissionTo('shipping_methods.remove');
        $response = $this->actingAs($this->{$user})
            ->deleteJson('/shipping-methods/id:' . $shippingMethod->getKey());

        $response
            ->assertStatus(422);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteWithRelations($user): void
    {
        $this->{$user}->givePermissionTo('shipping_methods.remove');

        $this->shipping_method = ShippingMethod::factory()->create();

        Order::factory()->create([
            'shipping_method_id' => $this->shipping_method->getKey(),
        ]);

        $response = $this->actingAs($this->{$user})
            ->deleteJson('/shipping-methods/id:' . $this->shipping_method->getKey());
        $response->assertStatus(400);
        $this->assertDatabaseHas('shipping_methods', $this->shipping_method->toArray());
    }
}
