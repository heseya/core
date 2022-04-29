<?php

namespace Feature;

use App\Enums\ConditionType;
use App\Enums\DiscountTargetType;
use App\Enums\DiscountType;
use App\Models\ConditionGroup;
use App\Models\Discount;
use App\Models\Item;
use App\Models\Option;
use App\Models\PriceRange;
use App\Models\Product;
use App\Models\ProductSet;
use App\Models\Schema;
use App\Models\ShippingMethod;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private ShippingMethod $shippingMethod;
    private ProductSet $category;
    private ProductSet $brand;
    private Product $product;
    private string $email;
    private Product $productWithSchema;
    private Schema $schema;
    private Option $option;
    private Item $item;

    public function setUp(): void
    {
        parent::setUp();

        $this->email = $this->faker->freeEmail;

        $this->shippingMethod = ShippingMethod::factory()->create(['public' => true]);
        $lowRange = PriceRange::create(['start' => 0]);
        $lowRange->prices()->create(['value' => 8.11]);

        $highRange = PriceRange::create(['start' => 210]);
        $highRange->prices()->create(['value' => 0.0]);

        $this->shippingMethod->priceRanges()->saveMany([$lowRange, $highRange]);

        $this->product = Product::factory()->create([
            'public' => true,
            'price' => 4600.0,
        ]);

        $this->productWithSchema = Product::factory()->create([
            'price' => 100,
            'public' => true,
        ]);
        $this->schema = Schema::factory()->create([
            'type' => 'select',
            'price' => 0,
            'hidden' => false,
        ]);
        $this->productWithSchema->schemas()->sync([$this->schema->getKey()]);
        $this->option = $this->schema->options()->create([
            'name' => 'XL',
            'price' => 0,
        ]);
        $this->item = Item::factory()->create();
        $this->option->items()->sync([$this->item->getKey()]);
    }

    public function testCartProcessUnauthorized(): void
    {
        $response = $this->postJson('/cart/process', [
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $this->product->getKey(),
                    'quantity' => 2,
                    'schemas' => [],
                ],
            ],
        ]);

        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCartProcessSimple($user): void
    {
        $this->$user->givePermissionTo('cart.verify');

        $response = $this->actingAs($this->$user)->postJson('/cart/process', [
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $this->product->getKey(),
                    'quantity' => 2,
                    'schemas' => [],
                ],
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonFragment([
                'cart_total_initial' => 9200,
                'cart_total' => 9200,
                'shipping_price_initial' => 0,
                'shipping_price' => 0,
                'summary' => 9200,
                'coupons' => [],
                'sales' => [],
            ])
            ->assertJsonFragment([
                'cartitem_id' => '1',
                'price' => 4600,
                'price_discounted' => 4600,
            ]);
    }

    public function couponOrSaleProvider(): array
    {
        return [
            'as user coupon' => ['user', true],
            'as application coupon' => ['application', true],
            'as user sale' => ['user', false],
            'as application sale' => ['application', false],
        ];
    }

    /**
     * @dataProvider couponOrSaleProvider
     */
    public function testCartProcess($user, $coupon): void
    {
        $this->$user->givePermissionTo('cart.verify');

        $code = $coupon ? [] : ['code' => null];

        $discountApplied = Discount::factory()->create([
            'description' => 'Testowy kupon obowiązujący',
            'name' => 'Testowy kupon obowiązujący',
            'value' => 10,
            'type' => DiscountType::PERCENTAGE,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
        ] + $code);

        $discount = Discount::factory()->create([
            'description' => 'Testowy kupon',
            'name' => 'Testowy kupon',
            'value' => 100,
            'type' => DiscountType::AMOUNT,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
        ] + $code);

        $conditionGroup = ConditionGroup::create();

        $conditionGroup->conditions()->create([
            'type' => ConditionType::DATE_BETWEEN,
            'value' => [
                'start_at' => Carbon::tomorrow(),
                'is_in_range' => true,
            ],
        ]);

        $discount->conditionGroups()->attach($conditionGroup);

        $coupons = $coupon ? [
            'coupons' => [
                $discount->code,
                $discountApplied->code,
            ],
        ] : [];

        $response = $this->actingAs($this->$user)->postJson('/cart/process', [
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $this->product->getKey(),
                    'quantity' => 2,
                    'schemas' => [],
                ],
            ],
        ] + $coupons);

        $result = $coupon ? ['sales' => []] : ['coupons' => []];
        $discountCode1 = $coupon ? ['code' => $discountApplied->code] : [];
        $discountCode2 = $coupon ? ['code' => $discount->code] : [];

        $response
            ->assertOk()
            ->assertJsonFragment([
                'cart_total_initial' => 9200,
                'cart_total' => 8280,
                'shipping_price_initial' => 0,
                'shipping_price' => 0,
                'summary' => 8280,
            ] + $result)
            ->assertJsonFragment([
                'cartitem_id' => '1',
                'price' => 4600,
                'price_discounted' => 4600,
            ])
            ->assertJsonFragment([
                'id' => $discountApplied->getKey(),
                'name' => $discountApplied->name,
                'value' => 920,
            ] + $discountCode1)
            ->assertJsonMissing([
                'id' => $discount->getKey(),
                'name' => $discount->name,
                'value' => 100,
            ] + $discountCode2);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCartProcessProductNotAvailable($user): void
    {
        $this->$user->givePermissionTo('cart.verify');

        $this->item->deposits()->create([
            'quantity' => 1,
        ]);

        $response = $this->actingAs($this->$user)->postJson('/cart/process', [
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $this->product->getKey(),
                    'quantity' => 2,
                    'schemas' => [],
                ],
                [
                    'cartitem_id' => '2',
                    'product_id' => $this->productWithSchema->getKey(),
                    'quantity' => 2,
                    'schemas' => [
                        $this->schema->getKey() => $this->option->getKey(),
                    ],
                ],
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonFragment([
                'cart_total_initial' => 9200,
                'cart_total' => 9200,
                'shipping_price_initial' => 0,
                'shipping_price' => 0,
                'summary' => 9200,
                'coupons' => [],
                'sales' => [],
            ])
            ->assertJsonFragment([
                'cartitem_id' => '1',
                'price' => 4600,
                'price_discounted' => 4600,
            ])->assertJsonMissing([
                'cartitem_id' => '2',
                'price' => 100,
                'price_discounted' => 100,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCartProcessProductDoesntExist($user): void
    {
        $this->$user->givePermissionTo('cart.verify');

        Discount::factory()->create([
            'target_type' => DiscountTargetType::CHEAPEST_PRODUCT,
            'code' => null,
            'value' => 10,
        ]);

        $this->item->deposits()->create([
            'quantity' => 1,
        ]);

        $response = $this->actingAs($this->$user)->postJson('/cart/process', [
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => 'ad6ed111-6111-4e11-bb11-ec65cecf8a11',
                    'quantity' => 1,
                    'schemas' => [],
                ],
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonFragment([
                'cart_total_initial' => 0,
                'cart_total' => 0,
                'coupons' => [],
                'sales' => [],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCartProcessFull($user): void
    {
        $this->$user->givePermissionTo('cart.verify');

        $saleApplied = Discount::factory()->create(
            [
                'description' => 'Testowa promocja',
                'name' => 'Testowa promocja obowiązująca',
                'value' => 10,
                'type' => DiscountType::PERCENTAGE,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => true,
                'code' => null,
            ],
        );

        $saleApplied->products()->attach($this->product->getKey());

        $sale = Discount::factory()->create(
            [
                'description' => 'Testowa promocja',
                'name' => 'Testowa promocja',
                'value' => 100,
                'type' => DiscountType::AMOUNT,
                'target_type' => DiscountTargetType::ORDER_VALUE,
                'target_is_allow_list' => true,
                'code' => null,
            ],
        );

        $conditionGroup = ConditionGroup::create();

        $conditionGroup->conditions()->create([
            'type' => ConditionType::DATE_BETWEEN,
            'value' => [
                'start_at' => Carbon::tomorrow(),
                'is_in_range' => true,
            ],
        ]);

        $sale->conditionGroups()->attach($conditionGroup);

        $couponApplied = Discount::factory()->create([
            'description' => 'Testowy kupon',
            'name' => 'Testowy kupon obowiązujący',
            'value' => 500,
            'type' => DiscountType::AMOUNT,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
        ]);

        $coupon = Discount::factory()->create([
            'description' => 'Testowy kupon',
            'name' => 'Testowy kupon',
            'value' => 10,
            'type' => DiscountType::PERCENTAGE,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
        ]);

        $conditionGroup2 = ConditionGroup::create();

        $conditionGroup2->conditions()->create([
            'type' => ConditionType::DATE_BETWEEN,
            'value' => [
                'start_at' => Carbon::tomorrow(),
                'is_in_range' => true,
            ],
        ]);

        $coupon->conditionGroups()->attach($conditionGroup2);

        $this->item->deposits()->create([
            'quantity' => 1,
        ]);

        $response = $this->actingAs($this->$user)->postJson('/cart/process', [
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $this->product->getKey(),
                    'quantity' => 2,
                    'schemas' => [],
                ],
                [
                    'cartitem_id' => '2',
                    'product_id' => $this->productWithSchema->getKey(),
                    'quantity' => 2,
                    'schemas' => [
                        $this->schema->getKey() => $this->option->getKey(),
                    ],
                ],
            ],
            'coupons' => [
                $coupon->code,
                $couponApplied->code,
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonFragment([
                'cart_total_initial' => 9200,
                'cart_total' => 7780,
                'shipping_price_initial' => 0,
                'shipping_price' => 0,
                'summary' => 7780,
            ])
            ->assertJsonFragment([
                'cartitem_id' => '1',
                'price' => 4600,
                'price_discounted' => 4140,
            ])->assertJsonMissing([
                'cartitem_id' => '2',
                'price' => 100,
                'price_discounted' => 100,
            ])
            ->assertJsonFragment([
                'id' => $saleApplied->getKey(),
                'name' => $saleApplied->name,
                'value' => 920,
            ])
            ->assertJsonFragment([
                'id' => $couponApplied->getKey(),
                'name' => $couponApplied->name,
                'code' => $couponApplied->code,
                'value' => 500,
            ])
            ->assertJsonMissing([
                'id' => $sale->getKey(),
                'name' => $sale->name,
            ])
            ->assertJsonMissing([
                'id' => $coupon->getKey(),
                'name' => $coupon->name,
                'code' => $coupon->code,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCartProcessLessThanMinimal($user): void
    {
        $this->$user->givePermissionTo('cart.verify');

        $shippingMethod = ShippingMethod::factory()->create(['public' => true]);
        $lowRange = PriceRange::create(['start' => 0]);
        $lowRange->prices()->create(['value' => 10]);

        $shippingMethod->priceRanges()->saveMany([$lowRange]);

        $saleApplied = Discount::factory()->create([
            'description' => 'Testowa promocja',
            'name' => 'Testowa promocja obowiązująca',
            'value' => 99,
            'type' => DiscountType::PERCENTAGE,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
            'code' => null,
        ]);

        $couponOrder = Discount::factory()->create([
            'description' => 'Testowy kupon',
            'name' => 'Kupon order',
            'value' => 50,
            'type' => DiscountType::AMOUNT,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
        ]);

        $couponShipping = Discount::factory()->create([
            'description' => 'Testowy kupon',
            'name' => 'Kupon shipping',
            'value' => 15,
            'type' => DiscountType::AMOUNT,
            'target_type' => DiscountTargetType::SHIPPING_PRICE,
            'target_is_allow_list' => false,
        ]);

        $saleApplied->products()->attach($this->product->getKey());

        $this
            ->actingAs($this->$user)
            ->postJson('/cart/process', [
                'shipping_method_id' => $shippingMethod->getKey(),
                'items' => [
                    [
                        'cartitem_id' => '1',
                        'product_id' => $this->product->getKey(),
                        'quantity' => 1,
                        'schemas' => [],
                    ],
                ],
                'coupons' => [
                    $couponOrder->code,
                    $couponShipping->code,
                ],
            ])
            ->assertOk()
            ->assertJsonFragment([
                'cart_total_initial' => 4600,
                'cart_total' => 0,
                'shipping_price_initial' => 10,
                'shipping_price' => 0,
                'summary' => 0,
            ])
            ->assertJsonFragment([
                'cartitem_id' => '1',
                'price' => 4600,
                'price_discounted' => 46,
            ])
            ->assertJsonFragment([
                'id' => $saleApplied->getKey(),
                'name' => $saleApplied->name,
                'value' => 4554,
            ])
            ->assertJsonFragment([
                'id' => $couponOrder->getKey(),
                'name' => $couponOrder->name,
                'code' => $couponOrder->code,
                'value' => 46, // discount -50, but cart_total should be 46 when discount is applied
            ])
            ->assertJsonFragment([
                'id' => $couponShipping->getKey(),
                'name' => $couponShipping->name,
                'code' => $couponShipping->code,
                'value' => 10, // discount -15, but shipping_price_initial is 10
            ]);
    }

    /**
     * @dataProvider couponOrSaleProvider
     */
    public function testCartProcessWithNotExistingCoupon($user, $coupon): void
    {
        $this->$user->givePermissionTo('cart.verify');

        $data = $this->prepareDataForCouponTest($coupon);

        $response = $this->actingAs($this->$user)->postJson('/cart/process', [
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $this->product->getKey(),
                    'quantity' => 2,
                    'schemas' => [],
                ],
            ],
        ] + $data['coupons']);

        $result = $coupon ? ['sales' => []] : ['coupons' => []];
        $discountCode1 = $coupon ? ['code' => $data['discountApplied']->code] : [];
        $discountCode2 = $coupon ? ['code' => $data['discount']->code] : [];

        $response
            ->assertOk()
            ->assertJsonFragment([
                'cart_total_initial' => 9200,
                'cart_total' => 8280,
                'shipping_price_initial' => 0,
                'shipping_price' => 0,
                'summary' => 8280,
            ] + $result)
            ->assertJsonFragment([
                'cartitem_id' => '1',
                'price' => 4600,
                'price_discounted' => 4600,
            ])
            ->assertJsonFragment([
                'id' => $data['discountApplied']->getKey(),
                'name' => $data['discountApplied']->name,
                'value' => 920,
            ] + $discountCode1)
            ->assertJsonMissing([
                'id' => $data['discount']->getKey(),
                'name' => $data['discount']->name,
                'value' => 100,
            ] + $discountCode2);
    }

    /**
     * @dataProvider couponOrSaleProvider
     */
    public function testCartProcessCheapestProduct($user, $coupon): void
    {
        $this->$user->givePermissionTo('cart.verify');

        $code = $coupon ? [] : ['code' => null];

        $productDiscount = Discount::factory()
            ->create([
                'description' => 'Discount on product',
                'name' => 'Discount on product',
                'value' => 10,
                'type' => DiscountType::PERCENTAGE,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => false,
            ] + $code);

        $discount = Discount::factory()
            ->create([
                'description' => 'Discount on cheapest product',
                'name' => 'Discount on cheapest product',
                'value' => 5,
                'type' => DiscountType::PERCENTAGE,
                'target_type' => DiscountTargetType::CHEAPEST_PRODUCT,
                'target_is_allow_list' => true,
            ] + $code);

        $coupons = $coupon ? [
            'coupons' => [
                $discount->code,
                $productDiscount->code,
            ],
        ] : [];

        $response = $this
            ->actingAs($this->$user)
            ->postJson(
                '/cart/process',
                [
                    'shipping_method_id' => $this->shippingMethod->getKey(),
                    'items' => [
                        [
                            'cartitem_id' => '1',
                            'product_id' => $this->product->getKey(),
                            'quantity' => 2,
                            'schemas' => [],
                        ],
                    ],
                ] + $coupons
            );

        $result = $coupon ? ['sales' => []] : ['coupons' => []];
        $discountCode1 = $coupon ? ['code' => $productDiscount->code] : [];
        $discountCode2 = $coupon ? ['code' => $discount->code] : [];

        $response
            ->assertOk()
            ->assertJsonFragment([
                'cart_total_initial' => 9200,
                'cart_total' => 8073,
                'shipping_price_initial' => 0,
                'shipping_price' => 0,
                'summary' => 8073,
            ] + $result)
            ->assertJsonFragment([
                'cartitem_id' => '1',
                'price' => 4600,
                'price_discounted' => 4140,
                'quantity' => 1,
            ])
            ->assertJsonFragment([
                'cartitem_id' => '1',
                'price' => 4600,
                'price_discounted' => 3933,
                'quantity' => 1,
            ])
            ->assertJsonFragment([
                'id' => $productDiscount->getKey(),
                'name' => $productDiscount->name,
                'value' => 920,
            ] + $discountCode1)
            ->assertJsonFragment([
                'id' => $discount->getKey(),
                'name' => $discount->name,
                'value' => 207,
            ] + $discountCode2);
    }

    private function prepareDataForCouponTest($coupon): array
    {
        $code = $coupon ? [] : ['code' => null];

        $discountApplied = Discount::factory()->create([
            'description' => 'Testowy kupon obowiązujący',
            'name' => 'Testowy kupon obowiązujący',
            'value' => 10,
            'type' => DiscountType::PERCENTAGE,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
        ] + $code);

        $discount = Discount::factory()->create([
            'description' => 'Testowy kupon',
            'name' => 'Testowy kupon',
            'value' => 100,
            'type' => DiscountType::AMOUNT,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
        ] + $code);

        $conditionGroup = ConditionGroup::create();

        $conditionGroup->conditions()->create([
            'type' => ConditionType::DATE_BETWEEN,
            'value' => [
                'start_at' => Carbon::tomorrow(),
                'is_in_range' => true,
            ],
        ]);

        $discount->conditionGroups()->attach($conditionGroup);

        $coupons = $coupon ? [
            'coupons' => [
                $discount->code,
                $discountApplied->code,
                'blablabla',
            ],
        ] : [];

        return [
            'coupons' => $coupons,
            'discount' => $discount,
            'discountApplied' => $discountApplied,
        ];
    }
}
