<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Price;
use App\Models\PriceRange;
use App\Models\ShippingMethod;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ShippingMethodTest extends TestCase
{
    public ShippingMethod $shipping_method;
    public ShippingMethod $shipping_method_hidden;

    public array $expected;

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
    }

    public function testIndex(): void
    {
        $response = $this->getJson('/shipping-methods');
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data') // Should show only public shipping methods.
            ->assertJson(['data' => [
                0 => $this->expected,
            ]]);
    }

    public function testIndexByCountry(): void
    {
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

        $response = $this->postJson('/shipping-methods/filter', ['country' => 'DE']);
        $response
            ->assertOk()
            ->assertJsonCount(2, 'data') // Should show only public shipping methods.
            ->assertJsonFragment(['id' => $this->shipping_method->getKey()])
            ->assertJsonFragment(['id' => $shippingMethod2->getKey()]);
    }

    /**
     * Price range testing with no initial 'start' value of zero
     */
    public function testCreateByPriceRanges(): void
    {
        $shipping_method = [
            'name' => 'Test 4',
            'public' => false,
        ];

        $response = $this->actingAs($this->user)->postJson(
            '/shipping-methods', $shipping_method + [
               'price_ranges' => [
                   [
                       'start' => 10,
                       'value' => 10.37,
                   ],
                   [
                       'start' => 200,
                       'value' => 0,
                   ],
                   [
                       'start' => 0.1,
                       'value' => 5000,
                   ],
               ],
           ],
        );

        $response->assertStatus(422);
    }

    public function testCreate(): void
    {
        $response = $this->postJson('/shipping-methods');
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $shipping_method = [
            'name' => 'Test',
            'public' => true,
        ];

        $response = $this->postJson('/shipping-methods', $shipping_method + [
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
            // ->assertJsonFragment(['price_ranges' => [
            //     [
            //         'start' => 0,
            //         'prices' => [
            //             ['value' => 10.37],
            //         ],
            //     ],
            //     [
            //         'start' => 200,
            //         'prices' => [
            //             ['value' => 0],
            //         ],
            //     ],
            // ]]);
            // Doesnt work bacause of extra shit in the array

        $this->assertDatabaseHas('shipping_methods', $shipping_method);
    }

    /**
     * Price range testing with no initial 'start' value of zero
     */
    public function testUpdateByPriceRanges(): void
    {
        $shipping_method = [
            'name' => 'Test 5',
            'public' => false,
        ];

        $response = $this->actingAs($this->user)->patchJson(
            '/shipping-methods/id:' . $this->shipping_method->getKey(),
            $shipping_method + [
                'price_ranges' => [
                    [
                        'start' => 10,
                        'value' => 10.37,
                    ],
                    [
                        'start' => 200,
                        'value' => 0,
                    ],
                    [
                        'start' => 0.1,
                        'value' => 5000,
                    ],
                ],
            ],
        );

        $response->assertStatus(422);
    }

    public function testUpdate(): void
    {
        $response = $this->patchJson('/shipping-methods/id:' . $this->shipping_method->getKey());
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $shipping_method = [
            'name' => 'Test 2',
            'public' => false,
        ];

        $response = $this->patchJson(
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

    public function testDelete(): void
    {
        $response = $this->deleteJson('/shipping-methods/id:' . $this->shipping_method->getKey());
        $response->assertUnauthorized();
        $this->assertDatabaseHas('shipping_methods', $this->shipping_method->toArray());

        Passport::actingAs($this->user);

        $response = $this->deleteJson('/shipping-methods/id:' . $this->shipping_method->getKey());
        $response->assertNoContent();
        $this->assertDeleted($this->shipping_method);
    }

    public function testDeleteWithRelations(): void
    {
        Passport::actingAs($this->user);

        $this->shipping_method = ShippingMethod::factory()->create();

        Order::factory()->create([
            'shipping_method_id' => $this->shipping_method->getKey(),
        ]);

        $response = $this->deleteJson('/shipping-methods/id:' . $this->shipping_method->getKey());
        $response->assertStatus(400);
        $this->assertDatabaseHas('shipping_methods', $this->shipping_method->toArray());
    }
}
