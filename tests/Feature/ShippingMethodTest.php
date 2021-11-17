<?php

namespace Tests\Feature;

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
            'black_list' => true,
        ]);

        $lowRange = PriceRange::create(['start' => 0]);
        $lowRange->prices()->create([
            'value' => rand(8, 15) + (rand(0, 99) / 100),
        ]);

        $highRange = PriceRange::create(['start' => 210]);
        $highRange->prices()->create(['value' => 0.0]);

        $this->shipping_method->priceRanges()->saveMany([$lowRange, $highRange]);

        $this->shipping_method_hidden = ShippingMethod::factory()->create([
            'public' => false,
            'black_list' => true,
        ]);

        $lowRange = PriceRange::create(['start' => 0]);
        $lowRange->prices()->create([
            'value' => rand(8, 15) + (rand(0, 99) / 100),
        ]);

        $highRange = PriceRange::create(['start' => 210]);
        $highRange->prices()->create(['value' => 0.0]);

        $this->shipping_method_hidden->priceRanges()->saveMany([$lowRange, $highRange]);

        /**
         * Expected response
         */
        $this->expected = [
            'id' => $this->shipping_method->getKey(),
            'name' => $this->shipping_method->name,
            'public' => $this->shipping_method->public,
        ];

        $this->priceRangesWithNoInitialStart = [
            [
                'start' => $this->faker()->randomFloat(2,1, 49),
                'value' => $this->faker()->randomFloat(2, 20),
            ],
            [
                'start' => $this->faker()->randomFloat(2,50, 99),
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
        $this->$user->givePermissionTo('shipping_methods.show');

        $response = $this->actingAs($this->$user)->getJson('/shipping-methods');
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data') // Should show only public shipping methods.
            ->assertJson(['data' => [
                0 => $this->expected,
            ]]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexHidden($user): void
    {
        $this->$user->givePermissionTo(['shipping_methods.show', 'shipping_methods.show_hidden']);

        $response = $this->actingAs($this->$user)->getJson('/shipping-methods');
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
        $this->$user->givePermissionTo('shipping_methods.show');

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

        $response = $this->actingAs($this->$user)->getJson('/shipping-methods');
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data') // Should show only public shipping methods.
            ->assertJson(['data' => [
                0 => $this->expected,
            ]])
            ->assertJsonCount(1, 'data.0.payment_methods')
            ->assertJsonFragment(['id' => $paymentMethod->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexWithPaymentMethodsShowHidden($user): void
    {
        $this->$user->givePermissionTo(['shipping_methods.show', 'payment_methods.show_hidden']);

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

        $response = $this->actingAs($this->$user)->getJson('/shipping-methods');
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data') // Should show only public shipping methods.
            ->assertJson(['data' => [
                0 => $this->expected,
            ]])
            ->assertJsonCount(2, 'data.0.payment_methods')
            ->assertJsonFragment(['id' => $paymentMethod->getKey()])
            ->assertJsonFragment(['id' => $paymentMethodHidden->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexWithPaymentMethodsEdit($user): void
    {
        $this->$user->givePermissionTo(['shipping_methods.show', 'shipping_methods.edit']);

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

        $response = $this->actingAs($this->$user)->getJson('/shipping-methods');
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data') // Should show only public shipping methods.
            ->assertJson(['data' => [
                0 => $this->expected,
            ]])
            ->assertJsonCount(2, 'data.0.payment_methods')
            ->assertJsonFragment(['id' => $paymentMethod->getKey()])
            ->assertJsonFragment(['id' => $paymentMethodHidden->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexByCountry($user): void
    {
        $this->$user->givePermissionTo('shipping_methods.show');

        // All countries without Germany
        $shippingMethod = ShippingMethod::factory()->create([
            'public' => true,
            'black_list' => true,
        ]);
        $shippingMethod->countries()->sync(['DE']);

        // Only Germany
        $shippingMethod2 = ShippingMethod::factory()->create([
            'public' => true,
            'black_list' => false,
        ]);
        $shippingMethod2->countries()->sync(['DE']);

        $response = $this->actingAs($this->$user)
            ->json('GET', '/shipping-methods', ['country' => 'DE']);
        $response
            ->assertOk()
            ->assertJsonCount(2, 'data') // Should show only public shipping methods.
            ->assertJsonFragment(['id' => $this->shipping_method->getKey()])
            ->assertJsonFragment(['id' => $shippingMethod2->getKey()]);
    }

    /**
     * Price range testing with no initial 'start' value of zero
     *
     * @dataProvider authProvider
     */
    public function testCreateByPriceRanges($user): void
    {
        $this->$user->givePermissionTo('shipping_methods.add');

        $shipping_method = [
            'name' => 'Test 4',
            'public' => false,
        ];

        $response = $this->actingAs($this->$user)->postJson(
            '/shipping-methods', $shipping_method + [
               'price_ranges' => $this->priceRangesWithNoInitialStart,
           ],
        );

        $response->assertStatus(422);
    }

    /**
     * Price range testing with duplicate "start" values
     *
     * @dataProvider authProvider
     */
    public function testCreateByDuplicatePriceRanges($user): void
    {
        $this->$user->givePermissionTo('shipping_methods.add');

        $shipping_method = [
            'name' => 'Test 5',
            'public' => false,
        ];

        $response = $this->actingAs($this->$user)->postJson(
            '/shipping-methods', $shipping_method + [
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
                   ]
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
        $this->$user->givePermissionTo('shipping_methods.add');

        $shipping_method = [
            'name' => 'Test',
            'public' => true,
        ];

        $response = $this->actingAs($this->$user)
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
     * Price range testing with no initial 'start' value of zero
     *
     * @dataProvider authProvider
     */
    public function testUpdateByPriceRanges($user): void
    {
        $this->$user->givePermissionTo('shipping_methods.edit');

        $shipping_method = [
            'name' => 'Test 6',
            'public' => false,
        ];

        $response = $this->actingAs($this->$user)->patchJson(
            '/shipping-methods/id:' . $this->shipping_method->getKey(),
            $shipping_method + [
                'price_ranges' => $this->priceRangesWithNoInitialStart,
            ],
        );

        $response->assertStatus(422);
    }

    /**
     * Price range testing with duplicate "start" values
     *
     * @dataProvider authProvider
     */
    public function testUpdateByDuplicatePriceRanges($user): void
    {
        $this->$user->givePermissionTo('shipping_methods.edit');

        $shipping_method = [
            'name' => 'Test 7',
            'public' => false,
        ];

        $response = $this->actingAs($this->$user)->patchJson(
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
                    ]
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
        $this->$user->givePermissionTo('shipping_methods.edit');

        $shipping_method = [
            'name' => 'Test 2',
            'public' => false,
        ];

        $response = $this->actingAs($this->$user)->patchJson(
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
        $this->$user->givePermissionTo('shipping_methods.remove');

        $response = $this->actingAs($this->$user)
            ->deleteJson('/shipping-methods/id:' . $this->shipping_method->getKey());
        $response->assertNoContent();
        $this->assertDeleted($this->shipping_method);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteWithRelations($user): void
    {
        $this->$user->givePermissionTo('shipping_methods.remove');

        $this->shipping_method = ShippingMethod::factory()->create();

        Order::factory()->create([
            'shipping_method_id' => $this->shipping_method->getKey(),
        ]);

        $response = $this->actingAs($this->$user)
            ->deleteJson('/shipping-methods/id:' . $this->shipping_method->getKey());
        $response->assertStatus(400);
        $this->assertDatabaseHas('shipping_methods', $this->shipping_method->toArray());
    }
}
