<?php

namespace Tests\Feature;

use App\Enums\ConditionType;
use App\Enums\DiscountTargetType;
use App\Enums\DiscountType;
use App\Enums\RoleType;
use App\Enums\ShippingType;
use App\Models\ConditionGroup;
use App\Models\Deposit;
use App\Models\Discount;
use App\Models\Item;
use App\Models\Option;
use App\Models\Order;
use App\Models\PriceRange;
use App\Models\Product;
use App\Models\Role;
use App\Models\Schema;
use App\Models\Status;
use App\Services\ProductService;
use App\Services\SchemaCrudService;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\Price\Dtos\PriceDto;
use Domain\ProductSet\ProductSet;
use Domain\SalesChannel\Models\SalesChannel;
use Domain\SalesChannel\SalesChannelRepository;
use Domain\ShippingMethod\Models\ShippingMethod;
use Heseya\Dto\DtoException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Tests\TestCase;
use Tests\Utils\FakeDto;

class CartTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    private ShippingMethod $shippingMethod;
    private ShippingMethod $digitalShippingMethod;
    private ProductSet $category;
    private ProductSet $brand;
    private Product $product;
    private Product $digitalProduct;
    private string $email;
    private Product $productWithSchema;
    private Schema $schema;
    private Option $option;
    private Option $option2;
    private Item $item;
    private ProductService $productService;
    private Currency $currency;
    private SchemaCrudService $schemaCrudService;
    private SalesChannel $salesChannel;

    public static function couponOrSaleProvider(): array
    {
        return [
            'as user coupon' => ['user', true],
            'as application coupon' => ['application', true],
            'as user sale' => ['user', false],
            'as application sale' => ['application', false],
        ];
    }

    /**
     * @throws UnknownCurrencyException
     * @throws RoundingNecessaryException
     * @throws NumberFormatException
     * @throws DtoException
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->email = $this->faker->freeEmail;

        $this->shippingMethod = ShippingMethod::factory()->create([
            'public' => true,
            'shipping_type' => ShippingType::ADDRESS,
        ]);

        $this->salesChannel = app(SalesChannelRepository::class)->getDefault();

        $this->productService = App::make(ProductService::class);
        $this->currency = Currency::DEFAULT;

        $this->schemaCrudService = App::make(SchemaCrudService::class);

        /** @var PriceRange $lowRange */
        $lowRange = PriceRange::query()->create([
            'start' => Money::zero($this->currency->value),
            'value' => Money::of(8.11, $this->currency->value),
        ]);

        /** @var PriceRange $highRange */
        $highRange = PriceRange::query()->create([
            'start' => Money::of(210, $this->currency->value),
            'value' => Money::of(0.0, $this->currency->value),
        ]);

        $this->shippingMethod->priceRanges()->saveMany([$lowRange, $highRange]);

        $this->product = $this->productService->create(FakeDto::productCreateDto([
            'prices_base' => [PriceDto::from(Money::of(4600.0, $this->currency->value))],
        ]));

        $this->productWithSchema = $this->productService->create(FakeDto::productCreateDto([
            'prices_base' => [PriceDto::from(Money::of(100.0, $this->currency->value))],
        ]));

        $this->schema = $this->schemaCrudService->store(
            FakeDto::schemaDto([
                'type' => 'select',
                'prices' => [PriceDto::from(Money::of(0, $this->currency->value))],
                'hidden' => false,
                'required' => false,
                'options' => [
                    [
                        'name' => 'XL',
                        'prices' => [PriceDto::from(Money::of(0, $this->currency->value))],
                    ],
                    [
                        'name' => 'L',
                        'prices' => [PriceDto::from(Money::of(100, $this->currency->value))],
                    ],
                ],
            ])
        );

        $this->productWithSchema->schemas()->sync([$this->schema->getKey()]);

        $this->option = $this->schema->options->where('name', 'XL')->first();

        $this->item = Item::factory()->create();
        $this->option->items()->sync([$this->item->getKey()]);

        $this->option2 = $this->schema->options->where('name', 'L')->first();

        $this->digitalProduct = $this->productService->create(FakeDto::productCreateDto([
            'shipping_digital' => true,
        ]));

        $this->digitalShippingMethod = ShippingMethod::factory()->create([
            'shipping_type' => ShippingType::DIGITAL,
        ]);
    }

    public function testCartProcessUnauthorized(): void
    {
        $response = $this->postJson('/cart/process', [
            'currency' => $this->currency,
            'sales_channel_id' => $this->salesChannel->getKey(),
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
    public function testCartProcessDigitalMethodForPhysicalProduct($user): void
    {
        $this->{$user}->givePermissionTo('cart.verify');

        $response = $this->actingAs($this->{$user})->postJson('/cart/process', [
            'currency' => $this->currency,
            'sales_channel_id' => $this->salesChannel->getKey(),
            'digital_shipping_method_id' => $this->digitalShippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $this->product->getKey(),
                    'quantity' => 2,
                    'schemas' => [],
                ],
            ],
        ]);

        $response->assertUnprocessable();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCartProcessPhysicalMethodForDigitalProduct($user): void
    {
        $this->{$user}->givePermissionTo('cart.verify');

        $response = $this->actingAs($this->{$user})->postJson('/cart/process', [
            'currency' => $this->currency,
            'sales_channel_id' => $this->salesChannel->getKey(),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $this->digitalProduct->getKey(),
                    'quantity' => 2,
                    'schemas' => [],
                ],
            ],
        ]);

        $response->assertUnprocessable();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCartProcessSimple($user): void
    {
        $this->{$user}->givePermissionTo('cart.verify');

        $response = $this->actingAs($this->{$user})->postJson('/cart/process', [
            'currency' => $this->currency,
            'sales_channel_id' => $this->salesChannel->getKey(),
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
            ->assertValid()->assertOk()
            ->assertJsonFragment([
                'cart_total_initial' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '9200.00',
                    'net' => '9200.00',
                ],
                'cart_total' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '9200.00',
                    'net' => '9200.00',
                ],
                'shipping_price_initial' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '0.00',
                    'net' => '0.00',
                ],
                'shipping_price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '0.00',
                    'net' => '0.00',
                ],
                'summary' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '9200.00',
                    'net' => '9200.00',
                ],
                'coupons' => [],
                'sales' => [],
            ])
            ->assertJsonFragment([
                'cartitem_id' => '1',
                'price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
                'price_discounted' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCartProcessWithMultipleSchemas(string $user): void
    {
        $this->{$user}->givePermissionTo('cart.verify');

        $this->item->deposits()->create([
            'quantity' => 1,
        ]);

        $response = $this->actingAs($this->{$user})->postJson('/cart/process', [
            'currency' => $this->currency,
            'sales_channel_id' => $this->salesChannel->getKey(),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $this->productWithSchema->getKey(),
                    'quantity' => 1,
                    'schemas' => [
                        $this->schema->getKey() => $this->option->getKey(),
                    ],
                ],
                [
                    'cartitem_id' => '2',
                    'product_id' => $this->productWithSchema->getKey(),
                    'quantity' => 1,
                    'schemas' => [
                        $this->schema->getKey() => $this->option2->getKey(),
                    ],
                ],
            ],
        ]);

        $response
            ->assertValid()->assertOk()
            ->assertJsonFragment([
                'cart_total_initial' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '300.00',
                    'net' => '300.00',
                ],
                'cart_total' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '300.00',
                    'net' => '300.00',
                ],
                'shipping_price_initial' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '0.00',
                    'net' => '0.00',
                ],
                'shipping_price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '0.00',
                    'net' => '0.00',
                ],
                'summary' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '300.00',
                    'net' => '300.00',
                ],
                'coupons' => [],
                'sales' => [],
            ])
            ->assertJsonFragment([
                'cartitem_id' => '1',
                'price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '100.00',
                    'net' => '100.00',
                ],
                'price_discounted' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '100.00',
                    'net' => '100.00',
                ],
            ])
            ->assertJsonFragment([
                'cartitem_id' => '2',
                'price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '200.00',
                    'net' => '200.00',
                ],
                'price_discounted' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '200.00',
                    'net' => '200.00',
                ],
            ]);
    }

    /**
     * @dataProvider couponOrSaleProvider
     */
    public function testCartProcess($user, $coupon): void
    {
        $this->markTestSkipped();

        $this->{$user}->givePermissionTo('cart.verify');

        $code = $coupon ? [] : ['code' => null];

        $discountApplied = Discount::factory()->create(
            [
                'description' => 'Testowy kupon obowiązujący',
                'name' => 'Testowy kupon obowiązujący',
                'percentage' => '10',
                'target_type' => DiscountTargetType::ORDER_VALUE,
                'target_is_allow_list' => true,
            ] + $code
        );

        $discount = Discount::factory()->create(
            [
                'description' => 'Testowy kupon',
                'name' => 'Testowy kupon',
                'value' => 100,
                'type' => DiscountType::AMOUNT,
                'target_type' => DiscountTargetType::ORDER_VALUE,
                'target_is_allow_list' => true,
            ] + $code
        );

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

        $response = $this->actingAs($this->{$user})->postJson(
            '/cart/process',
            [
                'currency' => $this->currency,
                'sales_channel_id' => $this->salesChannel->getKey(),
                'shipping_method_id' => $this->shippingMethod->getKey(),
                'items' => [
                    [
                        'cartitem_id' => '1',
                        'product_id' => $this->product->getKey(),
                        'quantity' => 2,
                        'schemas' => [],

                    ],
                ],
            ] + $coupons,
        );

        $result = $coupon ? ['sales' => []] : ['coupons' => []];
        $discountCode1 = $coupon ? ['code' => $discountApplied->code] : [];
        $discountCode2 = $coupon ? ['code' => $discount->code] : [];

        $response
            ->assertValid()->assertOk()
            ->assertJsonFragment(
                [
                    'cart_total_initial' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '9200.00',
                        'net' => '9200.00',
                    ],
                    'cart_total' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '8280.00',
                        'net' => '8280.00',
                    ],
                    'shipping_price_initial' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '0.00',
                        'net' => '0.00',
                    ],
                    'shipping_price' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '0.00',
                        'net' => '0.00',
                    ],
                    'summary' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '8280.00',
                        'net' => '8280.00',
                    ],
                ] + $result
            )
            ->assertJsonFragment([
                'cartitem_id' => '1',
                'price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
                'price_discounted' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
            ])
            ->assertJsonFragment(
                [
                    'id' => $discountApplied->getKey(),
                    'name' => $discountApplied->name,
                    'value' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '920.00',
                        'net' => '920.00',
                    ],
                ] + $discountCode1
            )
            ->assertJsonMissing(
                [
                    'id' => $discount->getKey(),
                    'name' => $discount->name,
                    'value' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '100.00',
                        'net' => '100.00',
                    ],
                ] + $discountCode2
            );
    }

    /**
     * @dataProvider authProvider
     */
    public function testCartProcessProductNotAvailable($user): void
    {
        $this->{$user}->givePermissionTo('cart.verify');

        $this->item->deposits()->create([
            'quantity' => 1,
        ]);

        $response = $this->actingAs($this->{$user})->postJson('/cart/process', [
            'currency' => $this->currency,
            'sales_channel_id' => $this->salesChannel->getKey(),
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
            ->assertValid()->assertOk()
            ->assertJsonFragment([
                'cart_total_initial' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '9200.00',
                    'net' => '9200.00',
                ],
                'cart_total' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '9200.00',
                    'net' => '9200.00',
                ],
                'shipping_price_initial' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '0.00',
                    'net' => '0.00',
                ],
                'shipping_price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '0.00',
                    'net' => '0.00',
                ],
                'summary' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '9200.00',
                    'net' => '9200.00',
                ],
                'coupons' => [],
                'sales' => [],
            ])
            ->assertJsonFragment([
                'cartitem_id' => '1',
                'price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
                'price_discounted' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
            ])->assertJsonMissing([
                'cartitem_id' => '2',
                'price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '100.00',
                    'net' => '100.00',
                ],
                'price_discounted' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '100.00',
                    'net' => '100.00',
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCartProcessProductDoesntExist($user): void
    {
        $this->{$user}->givePermissionTo('cart.verify');

        Discount::factory()->create([
            'target_type' => DiscountTargetType::CHEAPEST_PRODUCT,
            'code' => null,
            'percentage' => '10',
        ]);

        $this->item->deposits()->create([
            'quantity' => 1,
        ]);

        $response = $this->actingAs($this->{$user})->postJson('/cart/process', [
            'currency' => $this->currency,
            'sales_channel_id' => $this->salesChannel->getKey(),
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
            ->assertUnprocessable();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCartProcessFull($user): void
    {
        $this->markTestSkipped();

        $this->{$user}->givePermissionTo('cart.verify');

        $saleApplied = Discount::factory()->create(
            [
                'description' => 'Testowa promocja',
                'name' => 'Testowa promocja obowiązująca',
                'percentage' => '10',
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

        $response = $this->actingAs($this->{$user})->postJson('/cart/process', [
            'sales_channel_id' => $this->salesChannel->getKey(),
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
            ->assertValid()->assertOk()
            ->assertJsonFragment([
                'cart_total_initial' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '9200.00',
                    'net' => '9200.00',
                ],
                'cart_total' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '7780.00',
                    'net' => '7780.00',
                ],
                'shipping_price_initial' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '0.00',
                    'net' => '0.00',
                ],
                'shipping_price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '0.00',
                    'net' => '0.00',
                ],
                'summary' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '7780.00',
                    'net' => '7780.00',
                ],
            ])
            ->assertJsonFragment([
                'cartitem_id' => '1',
                'price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
                'price_discounted' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4140.00',
                    'net' => '4140.00',
                ],
            ])->assertJsonMissing([
                'cartitem_id' => '2',
                'price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '100.00',
                    'net' => '100.00',
                ],
                'price_discounted' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '100.00',
                    'net' => '100.00',
                ],
            ])
            ->assertJsonFragment([
                'id' => $saleApplied->getKey(),
                'name' => $saleApplied->name,
                'value' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '920.00',
                    'net' => '920.00',
                ],
            ])
            ->assertJsonFragment([
                'id' => $couponApplied->getKey(),
                'name' => $couponApplied->name,
                'code' => $couponApplied->code,
                'value' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '500.00',
                    'net' => '500.00',
                ],
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
        $this->markTestSkipped();

        $this->{$user}->givePermissionTo('cart.verify');

        $shippingMethod = ShippingMethod::factory()->create([
            'public' => true,
            'shipping_type' => ShippingType::ADDRESS,
        ]);
        $lowRange = PriceRange::create([
            'start' => Money::zero($this->currency->value),
            'value' => Money::of(10, $this->currency->value),
        ]);

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
            ->actingAs($this->{$user})
            ->postJson('/cart/process', [
                'sales_channel_id' => $this->salesChannel->getKey(),
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
            ->assertValid()->assertOk()
            ->assertJsonFragment([
                'cart_total_initial' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
                'cart_total' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '0.00',
                    'net' => '0.00',
                ],
                'shipping_price_initial' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '10.00',
                    'net' => '10.00',
                ],
                'shipping_price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '0.00',
                    'net' => '0.00',
                ],
                'summary' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '0.00',
                    'net' => '0.00',
                ],
            ])
            ->assertJsonFragment([
                'cartitem_id' => '1',
                'price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
                'price_discounted' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '46.00',
                    'net' => '46.00',
                ],
            ])
            ->assertJsonFragment([
                'id' => $saleApplied->getKey(),
                'name' => $saleApplied->name,
                'value' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4554.00',
                    'net' => '4554.00',
                ],
            ])
            ->assertJsonFragment([
                'id' => $couponOrder->getKey(),
                'name' => $couponOrder->name,
                'code' => $couponOrder->code,
                'value' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '46.00',
                    'net' => '46.00',
                ], // discount -50, but cart_total should be 46 when discount is applied
            ])
            ->assertJsonFragment([
                'id' => $couponShipping->getKey(),
                'name' => $couponShipping->name,
                'code' => $couponShipping->code,
                'value' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '10.00',
                    'net' => '10.00',
                ], // discount -15, but shipping_price_initial is 10
            ]);
    }

    /**
     * @dataProvider couponOrSaleProvider
     */
    public function testCartProcessWithNotExistingCoupon($user, $coupon): void
    {
        $this->{$user}->givePermissionTo('cart.verify');

        $data = $this->prepareDataForCouponTest($coupon);

        $response = $this->actingAs($this->{$user})->postJson(
            '/cart/process',
            [
                'currency' => $this->currency,
                'sales_channel_id' => $this->salesChannel->getKey(),
                'shipping_method_id' => $this->shippingMethod->getKey(),
                'items' => [
                    [
                        'cartitem_id' => '1',
                        'product_id' => $this->product->getKey(),
                        'quantity' => 2,
                        'schemas' => [],
                    ],
                ],
            ] + $data['coupons'],
        );

        $result = $coupon ? ['sales' => []] : ['coupons' => []];
        $discountCode1 = $coupon ? ['code' => $data['discountApplied']->code] : [];
        $discountCode2 = $coupon ? ['code' => $data['discount']->code] : [];

        $response
            ->assertValid()->assertOk()
            ->assertJsonFragment(
                [
                    'cart_total_initial' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '9200.00',
                        'net' => '9200.00',
                    ],
                    'cart_total' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '8280.00',
                        'net' => '8280.00',
                    ],
                    'shipping_price_initial' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '0.00',
                        'net' => '0.00',
                    ],
                    'shipping_price' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '0.00',
                        'net' => '0.00',
                    ],
                    'summary' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '8280.00',
                        'net' => '8280.00',
                    ],
                ] + $result
            )
            ->assertJsonFragment([
                'cartitem_id' => '1',
                'price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
                'price_discounted' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
            ])
            ->assertJsonFragment(
                [
                    'id' => $data['discountApplied']->getKey(),
                    'name' => $data['discountApplied']->name,
                    'value' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '920.00',
                        'net' => '920.00',
                    ],
                ] + $discountCode1
            )
            ->assertJsonMissing(
                [
                    'id' => $data['discount']->getKey(),
                    'name' => $data['discount']->name,
                    'value' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '100.00',
                        'net' => '100.00',
                    ],
                ] + $discountCode2
            );
    }

    /**
     * @dataProvider couponOrSaleProvider
     */
    public function testCartProcessCheapestProduct($user, $coupon): void
    {
        $this->{$user}->givePermissionTo('cart.verify');

        $code = $coupon ? [] : ['code' => null];

        $productDiscount = Discount::factory()
            ->create(
                [
                    'description' => 'Discount on product',
                    'name' => 'Discount on product',
                    'percentage' => '10',
                    'target_type' => DiscountTargetType::PRODUCTS,
                    'target_is_allow_list' => false,
                ] + $code
            );

        $discount = Discount::factory()
            ->create(
                [
                    'description' => 'Discount on cheapest product',
                    'name' => 'Discount on cheapest product',
                    'percentage' => '5',
                    'target_type' => DiscountTargetType::CHEAPEST_PRODUCT,
                    'target_is_allow_list' => true,
                ] + $code
            );

        $coupons = $coupon ? [
            'coupons' => [
                $discount->code,
                $productDiscount->code,
            ],
        ] : [];

        $response = $this
            ->actingAs($this->{$user})
            ->postJson(
                '/cart/process',
                [
                    'currency' => $this->currency,
                    'sales_channel_id' => $this->salesChannel->getKey(),
                    'shipping_method_id' => $this->shippingMethod->getKey(),
                    'items' => [
                        [
                            'cartitem_id' => '1',
                            'product_id' => $this->product->getKey(),
                            'quantity' => 2,
                            'schemas' => [],
                        ],
                    ],
                ] + $coupons,
            );

        $result = $coupon ? ['sales' => []] : ['coupons' => []];
        $discountCode1 = $coupon ? ['code' => $productDiscount->code] : [];
        $discountCode2 = $coupon ? ['code' => $discount->code] : [];

        $response
            ->assertValid()
            ->assertOk()
            ->assertJsonFragment(
                [
                    'cart_total_initial' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '9200.00',
                        'net' => '9200.00',
                    ],
                    'cart_total' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '8073.00',
                        'net' => '8073.00',
                    ],
                    'shipping_price_initial' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '0.00',
                        'net' => '0.00',
                    ],
                    'shipping_price' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '0.00',
                        'net' => '0.00',
                    ],
                    'summary' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '8073.00',
                        'net' => '8073.00',
                    ],
                ] + $result
            )
            ->assertJsonFragment([
                'cartitem_id' => '1',
                'price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
                'price_discounted' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4140.00',
                    'net' => '4140.00',
                ],
                'quantity' => 1,
            ])
            ->assertJsonFragment([
                'cartitem_id' => '1',
                'price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
                'price_discounted' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '3933.00',
                    'net' => '3933.00',
                ],
                'quantity' => 1,
            ])
            ->assertJsonFragment(
                [
                    'id' => $productDiscount->getKey(),
                    'name' => $productDiscount->name,
                    'value' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '920.00',
                        'net' => '920.00',
                    ],
                ] + $discountCode1
            )
            ->assertJsonFragment(
                [
                    'id' => $discount->getKey(),
                    'name' => $discount->name,
                    'value' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '207.00',
                        'net' => '207.00',
                    ],
                ] + $discountCode2
            );
    }

    /**
     * @dataProvider couponOrSaleProvider
     */
    public function testCartProcessCheapestProductWithSamePrice($user, $coupon): void
    {
        $this->markTestSkipped();

        $this->{$user}->givePermissionTo('cart.verify');

        $code = $coupon ? [] : ['code' => null];

        $productDiscount = Discount::factory()
            ->create(
                [
                    'description' => 'Discount on product',
                    'name' => 'Discount on product',
                    'percentage' => '100',
                    'target_type' => DiscountTargetType::PRODUCTS,
                    'target_is_allow_list' => false,
                ] + $code
            );

        $discount = Discount::factory()
            ->create(
                [
                    'description' => 'Discount on cheapest product',
                    'name' => 'Discount on cheapest product',
                    'percentage' => '5',
                    'target_type' => DiscountTargetType::CHEAPEST_PRODUCT,
                    'target_is_allow_list' => true,
                ] + $code
            );

        $coupons = $coupon ? [
            'coupons' => [
                $discount->code,
                $productDiscount->code,
            ],
        ] : [];

        $response = $this
            ->actingAs($this->{$user})
            ->postJson(
                '/cart/process',
                [
                    'sales_channel_id' => $this->salesChannel->getKey(),
                    'shipping_method_id' => $this->shippingMethod->getKey(),
                    'items' => [
                        [
                            'cartitem_id' => '1',
                            'product_id' => $this->product->getKey(),
                            'quantity' => 2,
                            'schemas' => [],
                        ],
                    ],
                ] + $coupons,
            );

        $result = $coupon ? ['sales' => []] : ['coupons' => []];
        $discountCode1 = $coupon ? ['code' => $productDiscount->code] : [];
        $discountCode2 = $coupon ? ['code' => $discount->code] : [];

        $response
            ->assertValid()->assertOk()
            ->assertJsonFragment(
                [
                    'cart_total_initial' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '9200.00',
                        'net' => '9200.00',
                    ],
                    'cart_total' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '0.02',
                        'net' => '0.02',
                    ],
                    'shipping_price_initial' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '0.00',
                        'net' => '0.00',
                    ],
                    'shipping_price' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '0.00',
                        'net' => '0.00',
                    ],
                    'summary' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '0.02',
                        'net' => '0.02',
                    ],
                ] + $result
            )
            ->assertJsonFragment([
                'cartitem_id' => '1',
                'price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
                'price_discounted' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '0.01',
                    'net' => '0.01',
                ],
                'quantity' => 2,
            ])
            ->assertJsonFragment(
                [
                    'id' => $productDiscount->getKey(),
                    'name' => $productDiscount->name,
                    'value' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '9200.00',
                        'net' => '9200.00',
                    ],
                ] + $discountCode1
            )
            ->assertJsonFragment(
                [
                    'id' => $discount->getKey(),
                    'name' => $discount->name,
                    'value' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '0.00',
                        'net' => '0.00',
                    ],
                ] + $discountCode2
            );
    }

    /**
     * @dataProvider authProvider
     *
     * @throws DtoException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     */
    public function testCartProcessWithDiscountValueAmountExtendPrice($user): void
    {
        $this->markTestSkipped();

        $this->{$user}->givePermissionTo('cart.verify');

        $product = $this->productService->create(FakeDto::productCreateDto([
            'prices_base' => [PriceDto::from(Money::of(10.0, $this->currency->value))],
        ]));
        $schema = $this->schemaCrudService->store(FakeDto::schemaDto([
            'type' => 'string',
            'prices' => [PriceDto::from(Money::of(20.0, $this->currency->value))],
            'hidden' => false,
        ]));
        $product->schemas()->save($schema);
        $product2 = $this->productService->create(FakeDto::productCreateDto([
            'prices_base' => [PriceDto::from(Money::of(100.0, $this->currency->value))],
        ]));

        $sale = Discount::factory()->create([
            'type' => DiscountType::AMOUNT,
            'target_type' => DiscountTargetType::PRODUCTS,
            'value' => 500,
            'target_is_allow_list' => true,
            'code' => null,
        ]);
        $sale->products()->attach($product->getKey());

        $response = $this->actingAs($this->{$user})->postJson('/cart/process', [
            'sales_channel_id' => $this->salesChannel->getKey(),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $product->getKey(),
                    'quantity' => 1,
                    'schemas' => [
                        $schema->getKey() => 'Test',
                    ],
                ],
                [
                    'cartitem_id' => '2',
                    'product_id' => $product2->getKey(),
                    'quantity' => 1,
                ],
            ],
        ]);

        $response
            ->assertValid()
            ->assertValid()->assertOk()
            ->assertJsonFragment(['summary' => [
                'currency' => Currency::DEFAULT->value,
                'gross' => '108.11',
                'net' => '108.11',
            ]]); // (10 (price) - 20 (discount)) + 100 + 8.11 (shipping)
    }

    /**
     * @dataProvider authProvider
     *
     * @throws DtoException
     */
    public function testCartProcessWithPromotionOnMultiProductWithSchema($user): void
    {
        $this->markTestSkipped();

        $this->{$user}->givePermissionTo('cart.verify');

        $productDto = FakeDto::productCreateDto([
            'prices_base' => [PriceDto::from(Money::of(10.0, $this->currency->value))],
        ]);
        $product = $this->productService->create($productDto);

        $schemaDto = FakeDto::schemaDto([
            'type' => 'string',
            'prices' => [PriceDto::from(Money::of(20.0, $this->currency->value))],
            'hidden' => false,
        ]);
        $schema = $this->schemaCrudService->store($schemaDto);

        $product->schemas()->save($schema);

        $sale = Discount::factory()->create([
            'type' => DiscountType::AMOUNT,
            'target_type' => DiscountTargetType::PRODUCTS,
            'value' => 25,
            'target_is_allow_list' => true,
            'code' => null,
        ]);
        $sale->products()->attach($product->getKey());

        $response = $this->actingAs($this->{$user})->postJson('/cart/process', [
            'sales_channel_id' => $this->salesChannel->getKey(),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $product->getKey(),
                    'quantity' => 3,
                    'schemas' => [
                        $schema->getKey() => 'Test',
                    ],
                ],
            ],
        ]);

        $response->assertValid()
            ->assertValid()->assertOk()
            ->assertJsonFragment(['summary' => [
                'currency' => Currency::DEFAULT->value,
                'gross' => '23.11',
                'net' => '23.11',
            ]]); // 3*((10(price) +20(schema)) -25(discount)) +8.11(shipping)
    }

    /**
     * @dataProvider authProvider
     */
    public function testCartProcessWithCouponCountAndProductNotInSetOnBlockList($user): void
    {
        $this->{$user}->givePermissionTo('cart.verify');

        [$product, $couponWithLimit, $coupon2] = $this->prepareCouponWithProductInSetAndCountConditions(false, false);

        $this->actingAs($this->{$user})->postJson('/cart/process', [
            'currency' => $this->currency,
            'sales_channel_id' => $this->salesChannel->getKey(),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $product->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
            ],
            'coupons' => [
                $couponWithLimit->code,
                $coupon2->code,
            ],
        ])
            ->assertValid()->assertOk()
            ->assertJsonCount(2, 'data.coupons');
    }

    /**
     * @dataProvider authProvider
     */
    public function testCartProcessWithCouponCountAndProductInSetOnBlockList($user): void
    {
        $this->{$user}->givePermissionTo('cart.verify');

        [$product, $couponWithLimit, $coupon2] = $this->prepareCouponWithProductInSetAndCountConditions(true, false);

        $this->actingAs($this->{$user})->postJson('/cart/process', [
            'currency' => $this->currency,
            'sales_channel_id' => $this->salesChannel->getKey(),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $product->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
            ],
            'coupons' => [
                $couponWithLimit->code,
                $coupon2->code,
            ],
        ])
            ->assertValid()->assertOk()
            ->assertJsonCount(1, 'data.coupons')
            ->assertJsonMissing([
                'id' => $couponWithLimit->getKey(),
                'name' => $couponWithLimit->name,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCartProcessWithCouponCountAndProductNotInSetOnAllowList($user): void
    {
        $this->{$user}->givePermissionTo('cart.verify');

        [$product, $couponWithLimit, $coupon2] = $this->prepareCouponWithProductInSetAndCountConditions(false, true);

        $this->actingAs($this->{$user})->postJson('/cart/process', [
            'currency' => $this->currency,
            'sales_channel_id' => $this->salesChannel->getKey(),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $product->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
            ],
            'coupons' => [
                $couponWithLimit->code,
                $coupon2->code,
            ],
        ])
            ->assertValid()->assertOk()
            ->assertJsonCount(1, 'data.coupons')
            ->assertJsonMissing([
                'id' => $couponWithLimit->getKey(),
                'name' => $couponWithLimit->name,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCartProcessWithCouponCountAndProductInSetOnAllowList($user): void
    {
        $this->{$user}->givePermissionTo('cart.verify');

        [$product, $couponWithLimit, $coupon2] = $this->prepareCouponWithProductInSetAndCountConditions(true, true);

        $this->actingAs($this->{$user})->postJson('/cart/process', [
            'currency' => $this->currency,
            'sales_channel_id' => $this->salesChannel->getKey(),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $product->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
            ],
            'coupons' => [
                $couponWithLimit->code,
                $coupon2->code,
            ],
        ])
            ->assertValid()->assertOk()
            ->assertJsonCount(2, 'data.coupons');
    }

    /**
     * @dataProvider couponOrSaleProvider
     *
     * @throws DtoException
     */
    public function testCartProcessRoundedValues($user, $coupon): void
    {
        $this->{$user}->givePermissionTo('cart.verify');

        $code = $coupon ? [] : ['code' => null];

        $product = $this->productService->create(FakeDto::productCreateDto([
            'prices_base' => [PriceDto::from(Money::of(4601.0, $this->currency->value))],
        ]));

        $discountApplied = Discount::factory()->create(
            [
                'description' => 'Testowy kupon obowiązujący',
                'name' => 'Testowy kupon obowiązujący',
                'percentage' => '2.5',
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => true,
            ] + $code
        );

        $discountApplied->products()->attach($product->getKey());

        $discountApplied2 = Discount::factory()->create(
            [
                'description' => 'Order value discount',
                'name' => 'Order value discount',
                'percentage' => '10',
                'target_type' => DiscountTargetType::ORDER_VALUE,
                'target_is_allow_list' => true,
            ] + $code
        );

        $coupons = $coupon ? [
            'coupons' => [
                $discountApplied->code,
                $discountApplied2->code,
            ],
        ] : [];

        $response = $this->actingAs($this->{$user})->postJson(
            '/cart/process',
            [
                'currency' => $this->currency,
                'sales_channel_id' => $this->salesChannel->getKey(),
                'shipping_method_id' => $this->shippingMethod->getKey(),
                'items' => [
                    [
                        'cartitem_id' => '1',
                        'product_id' => $product->getKey(),
                        'quantity' => 2,
                        'schemas' => [],
                    ],
                ],
            ] + $coupons,
        );

        $result = $coupon ? ['sales' => []] : ['coupons' => []];
        $discountCode = $coupon ? ['code' => $discountApplied->code] : [];
        $discountCode2 = $coupon ? ['code' => $discountApplied2->code] : [];

        // Yes those new values are accurate, old ones were wrong
        $response
            ->assertValid()->assertOk()
            ->assertJsonFragment(
                [
                    'cart_total_initial' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '9202.00',
                        'net' => '9202.00',
                    ],
                    'cart_total' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '8074.76',
                        'net' => '8074.76',
                    ],
                    'shipping_price_initial' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '0.00',
                        'net' => '0.00',
                    ],
                    'shipping_price' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '0.00',
                        'net' => '0.00',
                    ],
                    'summary' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '8074.76',
                        'net' => '8074.76',
                    ],
                ] + $result
            )
            ->assertJsonFragment([
                'cartitem_id' => '1',
                'price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4601.00',
                    'net' => '4601.00',
                ],
                'price_discounted' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4485.98',
                    'net' => '4485.98',
                ],
            ])
            ->assertJsonFragment(
                [
                    'id' => $discountApplied->getKey(),
                    'name' => $discountApplied->name,
                    'value' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '230.04',
                        'net' => '230.04',
                    ],
                ] + $discountCode
            )
            ->assertJsonFragment(
                [
                    'id' => $discountApplied2->getKey(),
                    'name' => $discountApplied2->name,
                    'value' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '897.20',
                        'net' => '897.20',
                    ],
                ] + $discountCode2
            );
    }

    /**
     * @dataProvider couponOrSaleProvider
     *
     * @throws DtoException
     */
    public function testCartProcessRoundedValuesCheapestProduct($user, $coupon): void
    {
        $this->{$user}->givePermissionTo('cart.verify');

        $code = $coupon ? [] : ['code' => null];

        $product = $this->productService->create(FakeDto::productCreateDto([
            'prices_base' => [PriceDto::from(Money::of(45.0, $this->currency->value))],
        ]));

        $discountApplied = Discount::factory()->create(
            [
                'description' => 'Testowy kupon obowiązujący',
                'name' => 'Testowy kupon obowiązujący',
                'percentage' => '10',
                'target_type' => DiscountTargetType::CHEAPEST_PRODUCT,
                'target_is_allow_list' => true,
                'priority' => 1,
            ] + $code
        );

        $discountApplied->products()->attach($product->getKey());

        $discountApplied2 = Discount::factory()->create(
            [
                'description' => 'Order value discount',
                'name' => 'Order value discount',
                'percentage' => '5',
                'target_type' => DiscountTargetType::CHEAPEST_PRODUCT,
                'target_is_allow_list' => true,
                'priority' => 0,
            ] + $code
        );

        $coupons = $coupon ? [
            'coupons' => [
                $discountApplied->code,
                $discountApplied2->code,
            ],
        ] : [];

        $response = $this->actingAs($this->{$user})->postJson(
            '/cart/process',
            [
                'currency' => $this->currency,
                'sales_channel_id' => $this->salesChannel->getKey(),
                'shipping_method_id' => $this->shippingMethod->getKey(),
                'items' => [
                    [
                        'cartitem_id' => '1',
                        'product_id' => $product->getKey(),
                        'quantity' => 2,
                        'schemas' => [],
                    ],
                ],
            ] + $coupons,
        );

        $result = $coupon ? ['sales' => []] : ['coupons' => []];
        $discountCode = $coupon ? ['code' => $discountApplied->code] : [];
        $discountCode2 = $coupon ? ['code' => $discountApplied2->code] : [];

        $response
            ->assertValid()->assertOk()
            ->assertJsonFragment(
                [
                    'cart_total_initial' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '90.00',
                        'net' => '90.00',
                    ],
                    'cart_total' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '83.48',
                        'net' => '83.48',
                    ],
                    'shipping_price_initial' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '8.11',
                        'net' => '8.11',
                    ],
                    'shipping_price' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '8.11',
                        'net' => '8.11',
                    ],
                    'summary' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '91.59',
                        'net' => '91.59',
                    ],
                ] + $result
            )
            ->assertJsonFragment([
                'cartitem_id' => '1',
                'price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '45.00',
                    'net' => '45.00',
                ],
                'price_discounted' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '45.00',
                    'net' => '45.00',
                ],
            ])
            ->assertJsonFragment([
                'cartitem_id' => '1',
                'price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '45.00',
                    'net' => '45.00',
                ],
                'price_discounted' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '38.48',
                    'net' => '38.48',
                ],
            ])
            ->assertJsonFragment(
                [
                    'id' => $discountApplied->getKey(),
                    'name' => $discountApplied->name,
                    'value' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '4.50',
                        'net' => '4.50',
                    ],
                ] + $discountCode
            )
            ->assertJsonFragment(
                [
                    'id' => $discountApplied2->getKey(),
                    'name' => $discountApplied2->name,
                    'value' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '2.02',
                        'net' => '2.02',
                    ],
                ] + $discountCode2
            );
    }

    /**
     * @dataProvider authProvider
     *
     * @throws DtoException
     */
    public function testCartProcessShippingTimeAndDateWhitUnlimitedStockShippingDate($user): void
    {
        $this->{$user}->givePermissionTo('cart.verify');

        $shipping_date = Carbon::now()->startOfDay()->addDays(10)->toIso8601String();

        $itemData = ['unlimited_stock_shipping_date' => $shipping_date];

        $item = Item::factory()->create($itemData);

        $product = $this->productService->create(FakeDto::productCreateDto([
            'prices_base' => [PriceDto::from(Money::of(4600.0, $this->currency->value))],
        ]));

        $product->items()->attach($item->getKey(), ['required_quantity' => 100]);

        $response = $this->actingAs($this->{$user})->postJson(
            '/cart/process',
            [
                'currency' => $this->currency,
                'sales_channel_id' => $this->salesChannel->getKey(),
                'shipping_method_id' => $this->shippingMethod->getKey(),
                'items' => [
                    [
                        'cartitem_id' => '1',
                        'product_id' => $product->getKey(),
                        'quantity' => 2,
                        'schemas' => [],
                    ],
                ],
            ]
        );

        $response
            ->assertValid()->assertOk()
            ->assertJsonFragment([
                'shipping_time' => null,
                'shipping_date' => $shipping_date,
            ]);
    }

    /**
     * @dataProvider authProvider
     *
     * @throws DtoException
     */
    public function testCartProcessShippingTimeAndDateWhitMultiProductsAndOneItem($user): void
    {
        $this->{$user}->givePermissionTo('cart.verify');

        $shippingDate = Carbon::now()->startOfDay()->addDays(10)->toIso8601String();

        $item = Item::factory()->create([
            'shipping_time' => null,
        ]);

        $product = $this->productService->create(FakeDto::productCreateDto([
            'prices_base' => [PriceDto::from(Money::of(4600.0, $this->currency->value))],
        ]));

        Deposit::factory([
            'quantity' => 150,
            'shipping_date' => $shippingDate,
            'shipping_time' => null,
        ])->create([
            'item_id' => $item->getKey(),
        ]);

        $product->items()->attach($item->getKey(), ['required_quantity' => 100]);

        $response = $this->actingAs($this->{$user})->postJson(
            '/cart/process',
            [
                'currency' => $this->currency,
                'sales_channel_id' => $this->salesChannel->getKey(),
                'shipping_method_id' => $this->shippingMethod->getKey(),
                'items' => [
                    [
                        'cartitem_id' => '1',
                        'product_id' => $product->getKey(),
                        'quantity' => 1,
                        'schemas' => [],
                    ],
                ],
            ]
        );

        $response
            ->assertValid()->assertOk()
            ->assertJsonFragment([
                'shipping_time' => null,
                'shipping_date' => $shippingDate,
            ]);

        $response = $this->actingAs($this->{$user})->postJson('/cart/process', [
            'currency' => $this->currency,
            'sales_channel_id' => $this->salesChannel->getKey(),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $product->getKey(),
                    'quantity' => 2,
                    'schemas' => [],
                ],
            ],
        ]);

        $response
            ->assertValid()->assertOk()
            ->assertJsonFragment([
                'shipping_time' => null,
                'shipping_date' => null,
            ]);

        $shippingDate2 = Carbon::now()->startOfDay()->addDays(20)->toIso8601String();

        Deposit::factory([
            'quantity' => 150,
            'shipping_time' => null,
            'shipping_date' => $shippingDate2,
        ])->create([
            'item_id' => $item->getKey(),
        ]);

        $response = $this->actingAs($this->{$user})->postJson('/cart/process', [
            'currency' => $this->currency,
            'sales_channel_id' => $this->salesChannel->getKey(),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $product->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
                [
                    'cartitem_id' => '2',
                    'product_id' => $product->getKey(),
                    'quantity' => 2,
                    'schemas' => [],
                ],
            ],
        ]);

        $response
            ->assertValid()->assertOk()
            ->assertJsonFragment([
                'shipping_time' => null,
                'shipping_date' => $shippingDate2,
            ]);
    }

    /**
     * @dataProvider authProvider
     *
     * @throws DtoException
     */
    public function testCartProcessShippingTimeAndDateWhitMultiProductsAndOneNotAvailable($user): void
    {
        $this->{$user}->givePermissionTo('cart.verify');

        $shippingDate = Carbon::now()->startOfDay()->addDays(10)->toIso8601String();

        $item = Item::factory()->create([
            'shipping_time' => null,
        ]);
        $item2 = Item::factory()->create([
            'shipping_time' => null,
        ]);

        $product = $this->productService->create(FakeDto::productCreateDto([
            'prices_base' => [PriceDto::from(Money::of(4600.0, $this->currency->value))],
        ]));
        $product2 = $this->productService->create(FakeDto::productCreateDto([
            'prices_base' => [PriceDto::from(Money::of(4600.0, $this->currency->value))],
        ]));

        Deposit::factory([
            'quantity' => 150,
            'shipping_date' => $shippingDate,
            'shipping_time' => null,
        ])->create([
            'item_id' => $item->getKey(),
        ]);

        $product->items()->attach($item->getKey(), ['required_quantity' => 100]);

        Deposit::factory([
            'quantity' => 1,
            'shipping_time' => 4,
        ])->create([
            'item_id' => $item2->getKey(),
        ]);

        $product2->items()->attach($item2->getKey(), ['required_quantity' => 1]);

        $response = $this->actingAs($this->{$user})->postJson('/cart/process', [
            'currency' => $this->currency,
            'sales_channel_id' => $this->salesChannel->getKey(),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $product->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
                [
                    'cartitem_id' => '2',
                    'product_id' => $product2->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
                [
                    'cartitem_id' => '3',
                    'product_id' => $this->productWithSchema->getKey(),
                    'quantity' => 2,
                    'schemas' => [
                        $this->schema->getKey() => $this->option->getKey(),
                    ],
                ],
            ],
        ]);

        $response
            ->assertValid()->assertOk()
            ->assertJsonFragment([
                'shipping_time' => null,
                'shipping_date' => $shippingDate,
            ]);

        $response = $this->actingAs($this->{$user})->postJson('/cart/process', [
            'currency' => $this->currency,
            'sales_channel_id' => $this->salesChannel->getKey(),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $product2->getKey(),
                    'quantity' => 1,
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
            ->assertValid()->assertOk()
            ->assertJsonFragment([
                'shipping_time' => 4,
                'shipping_date' => null,
            ]);

        $response = $this->actingAs($this->{$user})->postJson('/cart/process', [
            'currency' => $this->currency,
            'sales_channel_id' => $this->salesChannel->getKey(),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $product->getKey(),
                    'quantity' => 2,
                    'schemas' => [],
                ],
                [
                    'cartitem_id' => '2',
                    'product_id' => $product2->getKey(),
                    'quantity' => 2,
                    'schemas' => [],
                ],
            ],
        ]);

        $response
            ->assertValid()->assertOk()
            ->assertJsonFragment([
                'shipping_time' => null,
                'shipping_date' => null,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCartProcessProductWithSchemaAndItemNotAvailable($user): void
    {
        $this->{$user}->givePermissionTo('cart.verify');

        $this->item->deposits()->create([
            'quantity' => 3,
        ]);

        $this->productWithSchema->items()->attach($this->item->getKey(), ['required_quantity' => 2]);

        $response = $this->actingAs($this->{$user})->postJson('/cart/process', [
            'currency' => $this->currency,
            'sales_channel_id' => $this->salesChannel->getKey(),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $this->productWithSchema->getKey(),
                    'quantity' => 7,
                    'schemas' => [
                        $this->schema->getKey() => $this->option->getKey(),
                    ],
                ],
            ],
        ]);

        $response
            ->assertValid()->assertOk()
            ->assertJsonFragment([
                'cart_total_initial' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '0.00',
                    'net' => '0.00',
                ],
                'cart_total' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '0.00',
                    'net' => '0.00',
                ],
                'shipping_price_initial' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '8.11',
                    'net' => '8.11',
                ],
                'shipping_price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '8.11',
                    'net' => '8.11',
                ],
                'summary' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '8.11',
                    'net' => '8.11',
                ],
                'coupons' => [],
                'sales' => [],
            ])
            ->assertJsonMissing([
                'cartitem_id' => '1',
                'price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '100.00',
                    'net' => '100.00',
                ],
                'price_discounted' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '100.00',
                    'net' => '100.00',
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCartProcessProductWithItemNotAvailable($user): void
    {
        $this->{$user}->givePermissionTo('cart.verify');

        $this->item->deposits()->create([
            'quantity' => 3,
        ]);

        $this->product->items()->attach($this->item->getKey(), ['required_quantity' => 2]);

        $response = $this->actingAs($this->{$user})->postJson('/cart/process', [
            'currency' => $this->currency,
            'sales_channel_id' => $this->salesChannel->getKey(),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $this->product->getKey(),
                    'quantity' => 7,
                    'schemas' => [],
                ],
            ],
        ]);

        $response
            ->assertValid()->assertOk()
            ->assertJsonFragment([
                'cart_total_initial' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '0.00',
                    'net' => '0.00',
                ],
                'cart_total' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '0.00',
                    'net' => '0.00',
                ],
                'shipping_price_initial' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '8.11',
                    'net' => '8.11',
                ],
                'shipping_price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '8.11',
                    'net' => '8.11',
                ],
                'summary' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '8.11',
                    'net' => '8.11',
                ],
                'coupons' => [],
                'sales' => [],
            ])
            ->assertJsonMissing([
                'cartitem_id' => '1',
                'price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '100.00',
                    'net' => '100.00',
                ],
                'price_discounted' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '100.00',
                    'net' => '100.00',
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCartProcessProductWithSchemaAndItemAvailable($user): void
    {
        $this->{$user}->givePermissionTo('cart.verify');

        $this->item->deposits()->create([
            'quantity' => 6,
        ]);

        $this->productWithSchema->items()->attach($this->item->getKey(), ['required_quantity' => 2]);

        $response = $this->actingAs($this->{$user})->postJson('/cart/process', [
            'currency' => $this->currency,
            'sales_channel_id' => $this->salesChannel->getKey(),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $this->productWithSchema->getKey(),
                    'quantity' => 2,
                    'schemas' => [
                        $this->schema->getKey() => $this->option->getKey(),
                    ],
                ],
            ],
        ]);

        $response
            ->assertValid()->assertOk()
            ->assertJsonFragment([
                'cart_total_initial' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '200.00',
                    'net' => '200.00',
                ],
                'cart_total' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '200.00',
                    'net' => '200.00',
                ],
                'shipping_price_initial' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '8.11',
                    'net' => '8.11',
                ],
                'shipping_price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '8.11',
                    'net' => '8.11',
                ],
                'summary' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '208.11',
                    'net' => '208.11',
                ],
                'coupons' => [],
                'sales' => [],
            ])
            ->assertJsonFragment([
                'cartitem_id' => '1',
                'price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '100.00',
                    'net' => '100.00',
                ],
                'price_discounted' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '100.00',
                    'net' => '100.00',
                ],
            ]);
    }

    /**
     * @dataProvider couponOrSaleProvider
     */
    public function testCartProcessProductInChildrenSet($user, $coupon): void
    {
        $this->{$user}->givePermissionTo('cart.verify');

        $code = $coupon ? [] : ['code' => null];

        $parentSet = ProductSet::factory()->create([
            'public' => true,
            'name' => 'parent',
        ]);

        $childrenSet = ProductSet::factory()->create([
            'public' => true,
            'name' => 'children',
            'public_parent' => true,
            'parent_id' => $parentSet->getKey(),
        ]);

        $subChildrenSet = ProductSet::factory()->create([
            'public' => true,
            'name' => 'sub children',
            'public_parent' => true,
            'parent_id' => $childrenSet->getKey(),
        ]);

        $this->product->sets()->sync([$subChildrenSet->getKey()]);

        $discountApplied = Discount::factory()->create(
            [
                'description' => 'Testowy kupon obowiązujący',
                'name' => 'Testowy kupon obowiązujący',
                'percentage' => '10',
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => true,
            ] + $code
        );

        $discountApplied->productSets()->attach($parentSet);

        $discount = Discount::factory()->create(
            [
                'description' => 'Testowy kupon',
                'name' => 'Testowy kupon',
                'percentage' => '5',
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => false,
            ] + $code
        );

        $discount->productSets()->attach($parentSet);

        $coupons = $coupon ? [
            'coupons' => [
                $discount->code,
                $discountApplied->code,
            ],
        ] : [];

        $response = $this->actingAs($this->{$user})->postJson(
            '/cart/process',
            [
                'currency' => $this->currency,
                'sales_channel_id' => $this->salesChannel->getKey(),
                'shipping_method_id' => $this->shippingMethod->getKey(),
                'items' => [
                    [
                        'cartitem_id' => '1',
                        'product_id' => $this->product->getKey(),
                        'quantity' => 1,
                        'schemas' => [],

                    ],
                ],
            ] + $coupons,
        );

        $result = $coupon ? ['sales' => []] : ['coupons' => []];
        $discountCode1 = $coupon ? ['code' => $discountApplied->code] : [];
        $discountCode2 = $coupon ? ['code' => $discount->code] : [];

        $response
            ->assertValid()->assertOk()
            ->assertJsonFragment(
                [
                    'cart_total_initial' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '4600.00',
                        'net' => '4600.00',
                    ],
                    'cart_total' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '4140.00',
                        'net' => '4140.00',
                    ],
                    'shipping_price_initial' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '0.00',
                        'net' => '0.00',
                    ],
                    'shipping_price' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '0.00',
                        'net' => '0.00',
                    ],
                    'summary' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '4140.00',
                        'net' => '4140.00',
                    ],
                ] + $result
            )
            ->assertJsonFragment([
                'cartitem_id' => '1',
                'price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
                'price_discounted' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4140.00',
                    'net' => '4140.00',
                ],
            ])
            ->assertJsonFragment(
                [
                    'id' => $discountApplied->getKey(),
                    'name' => $discountApplied->name,
                    'value' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '460.00',
                        'net' => '460.00',
                    ],
                ] + $discountCode1
            )
            ->assertJsonMissing(
                [
                    'id' => $discount->getKey(),
                    'name' => $discount->name,
                    'value' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '0.00',
                        'net' => '0.00',
                    ],
                ] + $discountCode2
            );
    }

    /**
     * @dataProvider authProvider
     */
    public function testCartProcessSaleWithTargetProduct($user): void
    {
        $this->markTestSkipped();

        $this->{$user}->givePermissionTo('cart.verify');

        $productWithSale = $this->productService->create(FakeDto::productCreateDto());

        $sale = Discount::factory()->create([
            'code' => null,
            'description' => 'Promocja',
            'name' => 'Promocja na produkt',
            'value' => 100,
            'type' => DiscountType::AMOUNT,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ]);

        $sale->products()->attach($productWithSale->getKey());

        $saleShippingMethod = Discount::factory()->create([
            'code' => null,
            'description' => 'Promocja dostawa',
            'name' => 'Promocja na dostawę',
            'value' => 100,
            'type' => DiscountType::AMOUNT,
            'target_type' => DiscountTargetType::SHIPPING_PRICE,
            'target_is_allow_list' => true,
        ]);

        $saleShippingMethod->shippingMethods()->attach($saleShippingMethod->getKey());

        $this->actingAs($this->{$user})->postJson('/cart/process', [
            'sales_channel_id' => $this->salesChannel->getKey(),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $this->product->getKey(),
                    'quantity' => 2,
                    'schemas' => [],
                ],
            ],
        ])
            ->assertValid()->assertOk()
            ->assertJsonFragment([
                'cart_total_initial' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '9200.00',
                    'net' => '9200.00',
                ],
                'cart_total' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '9200.00',
                    'net' => '9200.00',
                ],
                'shipping_price_initial' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '0.00',
                    'net' => '0.00',
                ],
                'shipping_price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '0.00',
                    'net' => '0.00',
                ],
                'summary' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '9200.00',
                    'net' => '9200.00',
                ],
                'coupons' => [],
                'sales' => [],
            ])
            ->assertJsonCount(0, 'data.sales')
            ->assertJsonFragment([
                'cartitem_id' => '1',
                'price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
                'price_discounted' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
            ])
            ->assertJsonMissing(
                [
                    'id' => $sale->getKey(),
                    'name' => $sale->name,
                    'value' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '0.00',
                        'net' => '0.00',
                    ],
                ]
            )
            ->assertJsonMissing(
                [
                    'id' => $saleShippingMethod->getKey(),
                    'name' => $saleShippingMethod->name,
                    'value' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '0.00',
                        'net' => '0.00',
                    ],
                ]
            );
    }

    /**
     * @dataProvider couponOrSaleProvider
     */
    public function testCartProcessInactive($user, $coupon): void
    {
        $this->{$user}->givePermissionTo('cart.verify');

        $code = $coupon ? [] : ['code' => null];

        $discount = Discount::factory()->create(
            [
                'description' => 'Testowy kupon nieaktywny',
                'name' => 'Testowy kupon nieaktywny',
                'percentage' => '10',
                'target_type' => DiscountTargetType::ORDER_VALUE,
                'target_is_allow_list' => true,
                'active' => false,
            ] + $code
        );

        $conditionGroup = ConditionGroup::create();

        $conditionGroup->conditions()->create([
            'type' => ConditionType::DATE_BETWEEN,
            'value' => [
                'start_at' => Carbon::yesterday(),
                'end_at' => Carbon::tomorrow(),
                'is_in_range' => true,
            ],
        ]);

        $coupons = $coupon ? [
            'coupons' => [
                $discount->code,
            ],
        ] : [];

        $response = $this->actingAs($this->{$user})->postJson(
            '/cart/process',
            [
                'currency' => $this->currency,
                'sales_channel_id' => $this->salesChannel->getKey(),
                'shipping_method_id' => $this->shippingMethod->getKey(),
                'items' => [
                    [
                        'cartitem_id' => '1',
                        'product_id' => $this->product->getKey(),
                        'quantity' => 2,
                        'schemas' => [],
                    ],
                ],
            ] + $coupons,
        );

        $result = $coupon ? ['sales' => []] : ['coupons' => []];
        $discountCode = $coupon ? ['code' => $discount->code] : [];

        $response
            ->assertValid()->assertOk()
            ->assertJsonFragment(
                [
                    'cart_total_initial' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '9200.00',
                        'net' => '9200.00',
                    ],
                    'cart_total' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '9200.00',
                        'net' => '9200.00',
                    ],
                    'shipping_price_initial' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '0.00',
                        'net' => '0.00',
                    ],
                    'shipping_price' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '0.00',
                        'net' => '0.00',
                    ],
                    'summary' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '9200.00',
                        'net' => '9200.00',
                    ],
                ] + $result
            )
            ->assertJsonFragment([
                'cartitem_id' => '1',
                'price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
                'price_discounted' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
            ])
            ->assertJsonMissing(
                [
                    'id' => $discount->getKey(),
                    'name' => $discount->name,
                    'value' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '920.00',
                        'net' => '920.00',
                    ],
                ] + $discountCode
            );
    }

    /**
     * @dataProvider authProvider
     */
    public function testCartProcessPurchaseLimitMoreAvailable($user): void
    {
        $this->{$user}->givePermissionTo('cart.verify');

        $this->product->update([
            'purchase_limit_per_user' => 5,
        ]);

        $this->actingAs($this->{$user})->postJson('/cart/process', [
            'currency' => $this->currency,
            'sales_channel_id' => $this->salesChannel->getKey(),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $this->product->getKey(),
                    'quantity' => 2,
                    'schemas' => [],
                ],
            ],
        ])
            ->assertValid()->assertOk()
            ->assertJsonFragment([
                'cart_total_initial' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '9200.00',
                    'net' => '9200.00',
                ],
                'cart_total' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '9200.00',
                    'net' => '9200.00',
                ],
                'shipping_price_initial' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '0.00',
                    'net' => '0.00',
                ],
                'shipping_price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '0.00',
                    'net' => '0.00',
                ],
                'summary' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '9200.00',
                    'net' => '9200.00',
                ],
                'coupons' => [],
                'sales' => [],
            ])
            ->assertJsonFragment([
                'cartitem_id' => '1',
                'price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
                'price_discounted' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
                'quantity' => 2,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCartProcessPurchaseLimitLessAvailable($user): void
    {
        $this->{$user}->givePermissionTo('cart.verify');

        $this->product->update([
            'purchase_limit_per_user' => 1,
        ]);

        $this->actingAs($this->{$user})->postJson('/cart/process', [
            'currency' => $this->currency,
            'sales_channel_id' => $this->salesChannel->getKey(),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $this->product->getKey(),
                    'quantity' => 2,
                    'schemas' => [],
                ],
            ],
        ])
            ->assertValid()->assertOk()
            ->assertJsonFragment([
                'cart_total_initial' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
                'cart_total' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
                'shipping_price_initial' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '0.00',
                    'net' => '0.00',
                ],
                'shipping_price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '0.00',
                    'net' => '0.00',
                ],
                'summary' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
                'coupons' => [],
                'sales' => [],
            ])
            ->assertJsonFragment([
                'cartitem_id' => '1',
                'price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
                'price_discounted' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     *
     * @throws DtoException
     */
    public function testCartProcessPurchaseLimitWithSale($user): void
    {
        $this->markTestSkipped();

        $this->{$user}->givePermissionTo('cart.verify');

        $sale = Discount::factory()->create([
            'code' => null,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'name' => 'Sale for limited product',
            'type' => DiscountType::AMOUNT,
            'value' => 300,
            'target_is_allow_list' => true,
        ]);

        $conditionGroup = ConditionGroup::create();

        $conditionGroup->conditions()->create([
            'type' => ConditionType::PRODUCT_IN,
            'value' => [
                'products' => [
                    $this->product->getKey(),
                ],
                'is_allow_list' => true,
            ],
        ]);

        $sale->conditionGroups()->attach($conditionGroup);

        $this->product->update([
            'purchase_limit_per_user' => 0,
        ]);

        $product = $this->productService->create(FakeDto::productCreateDto([
            'prices_base' => [PriceDto::from(Money::of(1000.0, $this->currency->value))],
        ]));

        $this->actingAs($this->{$user})->postJson('/cart/process', [
            'currency' => $this->currency,
            'sales_channel_id' => $this->salesChannel->getKey(),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
                [
                    'cartitem_id' => '2',
                    'product_id' => $product->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
            ],
        ])
            ->assertValid()->assertOk()
            ->assertJsonFragment([
                'cart_total_initial' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '1000.00',
                    'net' => '1000.00',
                ],
                'cart_total' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '1000.00',
                    'net' => '1000.00',
                ],
                'shipping_price_initial' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '0.00',
                    'net' => '0.00',
                ],
                'shipping_price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '0.00',
                    'net' => '0.00',
                ],
                'summary' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '1000.00',
                    'net' => '1000.00',
                ],
                'coupons' => [],
            ])
            ->assertJsonCount(1, 'data.items')
            ->assertJsonMissing([
                'id' => $sale->getKey(),
                'name' => $sale->name,
                'value' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '300.00',
                    'net' => '300.00',
                ],
            ])
            ->assertJsonFragment([
                'cartitem_id' => '2',
                'price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '1000.00',
                    'net' => '1000.00',
                ],
                'price_discounted' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '1000.00',
                    'net' => '1000.00',
                ],
            ])
            ->assertJsonMissing([
                'cartitem_id' => '1',
                'price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
                'price_discounted' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCartProcessPurchaseLimitAlreadyPurchased($user): void
    {
        $this->{$user}->givePermissionTo('cart.verify');

        $this->product->update([
            'purchase_limit_per_user' => 1,
        ]);

        $order = Order::factory()->create([
            'paid' => true,
        ]);
        $this->{$user}->orders()->save($order);
        $order->products()->create([
            'product_id' => $this->product->getKey(),
            'quantity' => 1,
            'price_initial' => Money::of(4600, $this->currency->value),
            'price' => Money::of(4600, $this->currency->value),
            'name' => $this->product->name,
        ]);

        $this->actingAs($this->{$user})->postJson('/cart/process', [
            'currency' => $this->currency,
            'sales_channel_id' => $this->salesChannel->getKey(),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
            ],
        ])
            ->assertValid()->assertOk()
            ->assertJsonFragment([
                'cart_total_initial' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '0.00',
                    'net' => '0.00',
                ],
                'cart_total' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '0.00',
                    'net' => '0.00',
                ],
                'shipping_price_initial' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '8.11',
                    'net' => '8.11',
                ],
                'shipping_price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '8.11',
                    'net' => '8.11',
                ],
                'summary' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '8.11',
                    'net' => '8.11',
                ],
                'coupons' => [],
                'sales' => [],
                'items' => [],
            ])
            ->assertJsonCount(0, 'data.items');
    }

    /**
     * @dataProvider authProvider
     */
    public function testCartProcessPurchaseLimitAlreadyPurchasedNotPaid($user): void
    {
        $this->{$user}->givePermissionTo('cart.verify');

        $this->product->update([
            'purchase_limit_per_user' => 1,
        ]);

        $order = Order::factory()->create([
            'paid' => false,
        ]);
        $this->{$user}->orders()->save($order);
        $order->products()->create([
            'product_id' => $this->product->getKey(),
            'quantity' => 1,
            'price_initial' => Money::of(4600, $this->currency->value),
            'price' => Money::of(4600, $this->currency->value),
            'name' => $this->product->name,
        ]);

        $this->actingAs($this->{$user})->postJson('/cart/process', [
            'currency' => $this->currency,
            'sales_channel_id' => $this->salesChannel->getKey(),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
            ],
        ])
            ->assertValid()->assertOk()
            ->assertJsonFragment([
                'cart_total_initial' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
                'cart_total' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
                'shipping_price_initial' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '0.00',
                    'net' => '0.00',
                ],
                'shipping_price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '0.00',
                    'net' => '0.00',
                ],
                'summary' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
            ])
            ->assertJsonCount(1, 'data.items');
    }

    /**
     * @dataProvider authProvider
     */
    public function testCartProcessPurchaseLimitSetAfterPurchase($user): void
    {
        $this->{$user}->givePermissionTo('cart.verify');

        $order = Order::factory()->create([
            'paid' => true,
        ]);
        $this->{$user}->orders()->save($order);
        $order->products()->create([
            'product_id' => $this->product->getKey(),
            'quantity' => 2,
            'price_initial' => Money::of(4600, $this->currency->value),
            'price' => Money::of(4600, $this->currency->value),
            'name' => $this->product->name,
        ]);

        $this->product->update([
            'purchase_limit_per_user' => 1,
        ]);

        $this->actingAs($this->{$user})->postJson('/cart/process', [
            'currency' => $this->currency,
            'sales_channel_id' => $this->salesChannel->getKey(),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
            ],
        ])
            ->assertValid()->assertOk()
            ->assertJsonFragment([
                'cart_total_initial' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '0.00',
                    'net' => '0.00',
                ],
                'cart_total' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '0.00',
                    'net' => '0.00',
                ],
                'shipping_price_initial' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '8.11',
                    'net' => '8.11',
                ],
                'shipping_price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '8.11',
                    'net' => '8.11',
                ],
                'summary' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '8.11',
                    'net' => '8.11',
                ],
                'coupons' => [],
                'sales' => [],
                'items' => [],
            ])
            ->assertJsonCount(0, 'data.items');
    }

    /**
     * @dataProvider authProvider
     */
    public function testCartProcessPurchaseLimitCanceledOrder($user): void
    {
        $this->{$user}->givePermissionTo('cart.verify');

        $status = Status::factory()->create([
            'cancel' => true,
        ]);

        $order = Order::factory()->create([
            'status_id' => $status->getKey(),
        ]);
        $this->{$user}->orders()->save($order);
        $order->products()->create([
            'product_id' => $this->product->getKey(),
            'quantity' => 1,
            'price_initial' => Money::of(4600, $this->currency->value),
            'price' => Money::of(4600, $this->currency->value),
            'name' => $this->product->name,
        ]);

        $this->product->update([
            'purchase_limit_per_user' => 1,
        ]);

        $this->actingAs($this->{$user})->postJson('/cart/process', [
            'currency' => $this->currency,
            'sales_channel_id' => $this->salesChannel->getKey(),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
            ],
        ])
            ->assertValid()->assertOk()
            ->assertJsonFragment([
                'cart_total_initial' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
                'cart_total' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
                'shipping_price_initial' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '0.00',
                    'net' => '0.00',
                ],
                'shipping_price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '0.00',
                    'net' => '0.00',
                ],
                'summary' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
                'coupons' => [],
                'sales' => [],
            ])
            ->assertJsonFragment([
                'cartitem_id' => '1',
                'price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
                'price_discounted' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCartProcessPurchaseLimitProductWithSchema($user): void
    {
        $this->{$user}->givePermissionTo('cart.verify');

        $this->productWithSchema->update([
            'purchase_limit_per_user' => 1,
        ]);

        $response = $this->actingAs($this->{$user})->postJson('/cart/process', [
            'currency' => $this->currency,
            'sales_channel_id' => $this->salesChannel->getKey(),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $this->productWithSchema->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
                [
                    'cartitem_id' => '2',
                    'product_id' => $this->productWithSchema->getKey(),
                    'quantity' => 1,
                    'schemas' => [
                        $this->schema->getKey() => $this->option->getKey(),
                    ],
                ],
            ],
        ]);
        $response
            ->assertValid()->assertOk()
            ->assertJsonFragment([
                'cart_total_initial' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '100.00',
                    'net' => '100.00',
                ],
                'cart_total' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '100.00',
                    'net' => '100.00',
                ],
                'shipping_price_initial' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '8.11',
                    'net' => '8.11',
                ],
                'shipping_price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '8.11',
                    'net' => '8.11',
                ],
                'summary' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '108.11',
                    'net' => '108.11',
                ],
                'coupons' => [],
                'sales' => [],
            ])
            ->assertJsonFragment([
                'cartitem_id' => '1',
                'price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '100.00',
                    'net' => '100.00',
                ],
                'price_discounted' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '100.00',
                    'net' => '100.00',
                ],
            ])
            ->assertJsonMissing([
                'cartitem_id' => '2',
            ]);
    }

    public function testCartProcessPurchaseLimitNoAccount(): void
    {
        $role = Role::where('type', RoleType::UNAUTHENTICATED)->firstOrFail();
        $role->givePermissionTo('cart.verify');

        $this->product->update([
            'purchase_limit_per_user' => 1,
        ]);

        $this->postJson('/cart/process', [
            'currency' => $this->currency,
            'sales_channel_id' => $this->salesChannel->getKey(),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $this->product->getKey(),
                    'quantity' => 2,
                    'schemas' => [],
                ],
            ],
        ])
            ->assertValid()->assertOk()
            ->assertJsonFragment([
                'cart_total_initial' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
                'cart_total' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
                'shipping_price_initial' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '0.00',
                    'net' => '0.00',
                ],
                'shipping_price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '0.00',
                    'net' => '0.00',
                ],
                'summary' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
                'coupons' => [],
                'sales' => [],
            ])
            ->assertJsonFragment([
                'cartitem_id' => '1',
                'price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
                'price_discounted' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
            ]);
    }

    public function testCartProcessWithZeroSale(): void
    {
        $this->user->givePermissionTo('cart.verify');

        $discountApplied = Discount::factory()->create([
            'percentage' => '0',
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => false,
        ]);

        $response = $this->actingAs($this->user)->postJson('/cart/process', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
            ],
            'coupons' => [
                $discountApplied->code,
            ],
        ]);

        $response
            ->assertValid()->assertOk()
            ->assertJsonFragment([
                'cart_total_initial' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
                'cart_total' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
                'shipping_price_initial' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '0.00',
                    'net' => '0.00',
                ],
                'shipping_price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '0.00',
                    'net' => '0.00',
                ],
                'summary' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
            ])
            ->assertJsonFragment([
                'cartitem_id' => '1',
                'price' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
                'price_discounted' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '4600.00',
                    'net' => '4600.00',
                ],
            ])
            ->assertJsonFragment([
                'id' => $discountApplied->getKey(),
                'value' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '0.00',
                    'net' => '0.00',
                ],
            ]);
    }

    private function prepareDataForCouponTest($coupon): array
    {
        $this->markTestSkipped();

        $code = $coupon ? [] : ['code' => null];

        $discountApplied = Discount::factory()->create(
            [
                'description' => 'Testowy kupon obowiązujący',
                'name' => 'Testowy kupon obowiązujący',
                'value' => 10,
                'type' => DiscountType::PERCENTAGE,
                'target_type' => DiscountTargetType::ORDER_VALUE,
                'target_is_allow_list' => true,
            ] + $code
        );

        $discount = Discount::factory()->create(
            [
                'description' => 'Testowy kupon',
                'name' => 'Testowy kupon',
                'value' => 100,
                'type' => DiscountType::AMOUNT,
                'target_type' => DiscountTargetType::ORDER_VALUE,
                'target_is_allow_list' => true,
            ] + $code
        );

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

    /**
     * @throws RoundingNecessaryException
     * @throws DtoException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     */
    private function prepareCouponWithProductInSetAndCountConditions(bool $productInSet, bool $isAllowList): array
    {
        $product = $this->productService->create(FakeDto::productCreateDto([
            'prices_base' => [PriceDto::from(Money::of(49.0, $this->currency->value))],
        ]));

        $set = ProductSet::factory()->create([
            'public' => true,
        ]);

        if ($productInSet) {
            $product->sets()->sync([$set->getKey()]);
        }

        $couponWithLimit = Discount::factory()->create([
            'name' => 'Coupon with limit',
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'percentage' => '10',
            'target_is_allow_list' => true,
            'priority' => 0,
        ]);

        $conditionGroup = ConditionGroup::create();

        $conditionGroup->conditions()->create([
            'type' => ConditionType::PRODUCT_IN_SET,
            'value' => [
                'product_sets' => [
                    $set->getKey(),
                ],
                'is_allow_list' => $isAllowList,
            ],
        ]);

        $conditionGroup2 = ConditionGroup::create();
        $conditionGroup2->conditions()->create([
            'type' => ConditionType::COUPONS_COUNT,
            'value' => [
                'min_value' => 0,
                'max_value' => 1,
            ],
        ]);

        $conditionGroup2->conditions()->create([
            'type' => ConditionType::MAX_USES,
            'value' => [
                'max_uses' => 1,
            ],
        ]);

        $couponWithLimit->conditionGroups()->attach([$conditionGroup->getKey(), $conditionGroup2->getKey()]);

        $coupon2 = Discount::factory()->create([
            'name' => 'Coupon without limit',
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'percentage' => '20',
            'target_is_allow_list' => true,
            'priority' => 0,
        ]);

        return [$product, $couponWithLimit, $coupon2];
    }
}
