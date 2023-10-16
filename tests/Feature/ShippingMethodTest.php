<?php

namespace Tests\Feature;

use App\Enums\ShippingType;
use App\Enums\ValidationError;
use App\Models\Address;
use App\Models\App;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PriceRange;
use App\Models\ShippingMethod;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ShippingMethodTest extends TestCase
{
    use WithFaker;

    public ShippingMethod $shipping_method;
    public ShippingMethod $shipping_method_hidden;

    public array $expected;
    public array $priceRangesWithNoInitialStart;

    public function setUp(): void
    {
        parent::setUp();

        $this->shipping_method = ShippingMethod::factory()->create([
            'public' => true,
            'block_list' => true,
            'shipping_time_min' => 1,
            'shipping_time_max' => 2,
        ]);

        $lowRange = PriceRange::query()->create(['start' => 0]);
        $lowRange->prices()->create([
            'value' => mt_rand(8, 15) + (mt_rand(0, 99) / 100),
        ]);

        $highRange = PriceRange::query()->create(['start' => 210]);
        $highRange->prices()->create(['value' => 0.0]);

        $this->shipping_method->priceRanges()->saveMany([$lowRange, $highRange]);

        $this->shipping_method_hidden = ShippingMethod::factory()->create([
            'public' => false,
            'block_list' => true,
        ]);

        $lowRange = PriceRange::query()->create(['start' => 0]);
        $lowRange->prices()->create([
            'value' => mt_rand(8, 15) + (mt_rand(0, 99) / 100),
        ]);

        $highRange = PriceRange::query()->create(['start' => 210]);
        $highRange->prices()->create(['value' => 0.0]);

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

        $this->priceRangesWithNoInitialStart = [
            [
                'start' => $this->faker()->randomFloat(2, 1, 49),
                'value' => $this->faker()->randomFloat(2, 20),
            ],
            [
                'start' => $this->faker()->randomFloat(2, 50, 99),
                'value' => $this->faker()->randomFloat(2, 100),
            ],
            [
                'start' => $this->faker()->numberBetween(100, 1000),
                'value' => $this->faker()->numberBetween(100, 1000),
            ],
        ];
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
            ->assertJson(['data' => [
                0 => $this->expected,
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

        $this->actingAs($this->{$user})->json('GET', '/shipping-methods', [
            'ids' => [
                $this->shipping_method->getKey(),
            ],
        ])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJson(['data' => [
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
            ->assertJson(['data' => [
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
            ->assertJson(['data' => [
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
            ->assertJson(['data' => [
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
            'block_list' => true,
        ]);
        $shippingMethod->countries()->sync(['DE']);

        // Only Germany
        $shippingMethod2 = ShippingMethod::factory()->create([
            'public' => true,
            'block_list' => false,
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
            'shipping_type' => ShippingType::ADDRESS,
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
        ];

        $response = $this->actingAs($this->{$user})->postJson(
            '/shipping-methods',
            $shipping_method + [
                'price_ranges' => [
                    [
                        'start' => 0,
                        'value' => 0,
                    ],
                    [
                        'start' => 0,
                        'value' => 0,
                    ],
                    [
                        'start' => 10,
                        'value' => 0,
                    ],
                ],
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
            'block_list' => false,
            'shipping_time_min' => 2,
            'shipping_time_max' => 3,
            'shipping_type' => ShippingType::ADDRESS,
            'payment_on_delivery' => true,
        ];

        $response = $this->actingAs($this->{$user})
            ->postJson('/shipping-methods', $shipping_method + [
                'price_ranges' => [
                    [
                        'start' => 0,
                        'value' => 10.37,
                    ],
                    [
                        'start' => 200,
                        'value' => 0,
                    ],
                ],
            ]);

        $response
            ->assertCreated()
            ->assertJson(['data' => $shipping_method])
            ->assertJsonCount(2, 'data.price_ranges')
            ->assertJsonFragment(['start' => 0])
            ->assertJsonFragment(['value' => 10.37])
            ->assertJsonFragment(['start' => 200])
            ->assertJsonFragment(['value' => 0]);

        $this->assertDatabaseHas('shipping_methods', $shipping_method);
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
            'block_list' => false,
            'shipping_time_min' => 2,
            'shipping_time_max' => 3,
            'shipping_type' => ShippingType::ADDRESS,
            'payment_on_delivery' => false,
            'metadata' => [
                'attributeMeta' => 'attributeValue',
            ],
        ];

        $this
            ->actingAs($this->{$user})
            ->postJson('/shipping-methods', $shipping_method + [
                'price_ranges' => [
                    [
                        'start' => 0,
                        'value' => 10.37,
                    ],
                    [
                        'start' => 200,
                        'value' => 0,
                    ],
                ],
            ])
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
            'block_list' => false,
            'shipping_time_min' => 2,
            'shipping_time_max' => 3,
            'shipping_type' => ShippingType::ADDRESS,
            'payment_on_delivery' => true,
            'metadata_private' => [
                'attributeMetaPriv' => 'attributeValue',
            ],
        ];

        $this
            ->actingAs($this->{$user})
            ->postJson('/shipping-methods', $shipping_method + [
                'price_ranges' => [
                    [
                        'start' => 0,
                        'value' => 10.37,
                    ],
                    [
                        'start' => 200,
                        'value' => 0,
                    ],
                ],
            ])
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
            'block_list' => false,
            'shipping_time_min' => 2,
            'shipping_time_max' => 3,
            'shipping_type' => ShippingType::ADDRESS,
            'payment_on_delivery' => true,
        ];

        $response = $this->actingAs($this->{$user})
            ->json('POST', '/shipping-methods', $shipping_method + [
                'price_ranges' => [
                    [
                        'start' => 0,
                        'value' => 10.37,
                    ],
                    [
                        'start' => 200,
                        'value' => 0,
                    ],
                ],
            ]);

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
                'block_list' => false,
                'shipping_time_min' => 3,
                'shipping_time_max' => 2,
                'shipping_type' => ShippingType::ADDRESS,
                'payment_on_delivery' => false,
                'price_ranges' => [
                    [
                        'start' => 0,
                        'value' => 10.37,
                    ],
                    [
                        'start' => 200,
                        'value' => 0,
                    ],
                ],
            ]);

        $response->assertStatus(422);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateBlacklist($user): void
    {
        $this->{$user}->givePermissionTo('shipping_methods.add');

        $shipping_method = [
            'name' => 'Test',
            'public' => true,
            'block_list' => true,
            'shipping_time_min' => 2,
            'shipping_time_max' => 2,
            'shipping_type' => ShippingType::ADDRESS,
            'payment_on_delivery' => false,
        ];

        $response = $this->actingAs($this->{$user})
            ->postJson('/shipping-methods', $shipping_method + [
                'price_ranges' => [
                    [
                        'start' => 0,
                        'value' => 10.37,
                    ],
                ],
            ]);

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
            'block_list' => false,
            'shipping_time_min' => 2,
            'shipping_time_max' => 3,
            'shipping_type' => ShippingType::POINT,
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
            ->postJson('/shipping-methods', $shipping_method + [
                'price_ranges' => [
                    [
                        'start' => 0,
                        'value' => 10.37,
                    ],
                    [
                        'start' => 200,
                        'value' => 0,
                    ],
                ],
            ] + $shipping_points);

        $addressSaved = Address::where('name', 'test2')->first();

        $response
            ->assertCreated()
            ->assertJson(['data' => $shipping_method])
            ->assertJsonCount(2, 'data.shipping_points')
            ->assertJsonCount(2, 'data.price_ranges')
            ->assertJsonFragment(['start' => 0])
            ->assertJsonFragment(['value' => 10.37])
            ->assertJsonFragment(['start' => 200])
            ->assertJsonFragment(['value' => 0])
            ->assertJsonFragment(['shipping_type' => ShippingType::POINT]);

        $this->assertDatabaseHas('shipping_methods', $shipping_method + [
            'app_id' => $this->{$user} instanceof App ? $this->{$user}->getKey() : null,
        ])
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
    public function testCreatePaymentOnDeliveryWithPaymentMethods($user): void
    {
        $this->{$user}->givePermissionTo('shipping_methods.add');

        ShippingMethod::query()->delete();

        $paymentMethod = PaymentMethod::factory()->create([
            'public' => true,
        ]);

        $shipping_method = [
            'name' => 'Test',
            'public' => true,
            'block_list' => false,
            'shipping_time_min' => 2,
            'shipping_time_max' => 3,
            'shipping_type' => ShippingType::ADDRESS,
            'payment_on_delivery' => true,
            'payment_methods' => [
                $paymentMethod->getKey(),
            ],
        ];

        $this->actingAs($this->{$user})
            ->postJson('/shipping-methods', $shipping_method)
            ->assertUnprocessable()
            ->assertJsonFragment([
                'key' => ValidationError::PROHIBITEDIF,
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
                'shipping_type' => ShippingType::ADDRESS,
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
            ->assertJsonFragment(['shipping_type' => ShippingType::ADDRESS])
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

        $response = $this->actingAs($this->{$user})->patchJson(
            '/shipping-methods/id:' . $this->shipping_method->getKey(),
            $shipping_method + [
                'price_ranges' => [
                    [
                        'start' => 0,
                        'value' => 10.37,
                    ],
                    [
                        'start' => 0,
                        'value' => 0,
                    ],
                    [
                        'start' => 10,
                        'value' => 0,
                    ],
                ],
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
    public function testUpdate($user): void
    {
        $this->{$user}->givePermissionTo('shipping_methods.edit');

        $shipping_method = [
            'name' => 'Test 2',
            'public' => false,
            'block_list' => false,
        ];

        $response = $this->actingAs($this->{$user})->patchJson(
            '/shipping-methods/id:' . $this->shipping_method->getKey(),
            $shipping_method + [
                'price_ranges' => [
                    [
                        'start' => 0,
                        'value' => 10.37,
                    ],
                    [
                        'start' => 200,
                        'value' => 0,
                    ],
                ],
            ],
        );

        $response
            ->assertOk()
            ->assertJson(['data' => $shipping_method])
            ->assertJsonCount(2, 'data.price_ranges')
            ->assertJsonFragment(['start' => 0])
            ->assertJsonFragment(['value' => 10.37])
            ->assertJsonFragment(['start' => 200])
            ->assertJsonFragment(['value' => 0]);

        $this->assertDatabaseHas(
            'shipping_methods',
            $shipping_method + ['id' => $this->shipping_method->getKey()],
        );
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
            ->assertJson(['data' => [
                'id' => $this->shipping_method->getKey(),
                'name' => $this->shipping_method->name,
                'public' => $this->shipping_method->public,
                'block_list' => $this->shipping_method->block_list,
                'shipping_time_min' => $this->shipping_method->shipping_time_min,
                'shipping_time_max' => $this->shipping_method->shipping_time_max,
            ],
            ])
            ->assertJsonCount(2, 'data.price_ranges')
            ->assertJsonFragment(['start' => $this->shipping_method->priceRanges->first()->start])
            ->assertJsonFragment(['value' => $this->shipping_method->priceRanges->first()->prices()->first()->value])
            ->assertJsonFragment(['start' => $this->shipping_method->priceRanges->last()->start])
            ->assertJsonFragment(['value' => $this->shipping_method->priceRanges->last()->prices()->first()->value]);

        $this->assertDatabaseHas('shipping_methods', [
            'id' => $this->shipping_method->getKey(),
            'name' => $this->shipping_method->name,
            'public' => $this->shipping_method->public,
            'block_list' => $this->shipping_method->block_list,
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

        $this->shipping_method->update(['block_list' => false]);

        $shipping_method = [
            'name' => 'Test 2',
            'public' => false,
            'block_list' => true,
        ];

        $response = $this->actingAs($this->{$user})->patchJson(
            '/shipping-methods/id:' . $this->shipping_method->getKey(),
            $shipping_method + [
                'price_ranges' => [
                    [
                        'start' => 0,
                        'value' => 10.37,
                    ],
                ],
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

    /**
     * @dataProvider authProvider
     */
    public function testUpdatePaymentOnDelivery($user): void
    {
        $this->{$user}->givePermissionTo('shipping_methods.edit');

        $this->shipping_method->update([
            'payment_on_delivery' => false,
        ]);

        $paymentMethod = PaymentMethod::factory()->create([
            'public' => true,
        ]);

        $this->shipping_method->paymentMethods()->sync([
            $paymentMethod->getKey(),
        ]);

        $shipping_method = [
            'name' => 'Test 2',
            'public' => false,
            'block_list' => false,
            'payment_on_delivery' => true,
            'payment_methods' => [],
        ];

        $this->actingAs($this->{$user})->patchJson(
            '/shipping-methods/id:' . $this->shipping_method->getKey(),
            $shipping_method,
        )
            ->assertOk()
            ->assertJson(['data' => $shipping_method]);

        $this->assertDatabaseMissing('shipping_method_payment_method', [
            'payment_method_id' => $paymentMethod->getKey(),
            'shipping_method_id' => $this->shipping_method->getKey(),
        ]);
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
