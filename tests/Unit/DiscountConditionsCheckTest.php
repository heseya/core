<?php

namespace Unit;

use App\Dtos\CartDto;
use App\Enums\ConditionType;
use App\Models\ConditionGroup;
use App\Models\Discount;
use App\Models\DiscountCondition;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\Contracts\DiscountServiceContract;
use App\Services\ProductService;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\Price\Dtos\PriceDto;
use Domain\Price\Enums\DiscountConditionPriceType;
use Domain\ProductSet\ProductSet;
use Domain\SalesChannel\Models\SalesChannel;
use Domain\ShippingMethod\Models\ShippingMethod;
use Heseya\Dto\DtoException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use Tests\Utils\FakeDto;

class DiscountConditionsCheckTest extends TestCase
{
    use RefreshDatabase;

    private DiscountServiceContract $discountService;
    private ConditionGroup $conditionGroup;
    private Discount $discount;
    private ShippingMethod $shippingMethod;
    private Product $product;
    private ProductSet $set;
    private Currency $currency;
    private ProductService $productService;

    public static function dateBetweenPassProvider(): array
    {
        return [
            'in range' => [
                [
                    'start_at' => Carbon::create(2020, 02, 01, 10),
                    'end_at' => Carbon::create(2020, 02, 03, 10),
                    'is_in_range' => true,
                ],
            ],
            'not in range' => [
                [
                    'start_at' => Carbon::create(2020, 01, 01, 10),
                    'end_at' => Carbon::create(2020, 01, 20, 10),
                    'is_in_range' => false,
                ],
            ],
            'only start at in range' => [
                [
                    'start_at' => Carbon::create(2020, 02, 01, 10),
                    'is_in_range' => true,
                ],
            ],
            'only start at not in range' => [
                [
                    'start_at' => Carbon::create(2020, 02, 03, 10),
                    'is_in_range' => false,
                ],
            ],
            'only end at in range' => [
                [
                    'end_at' => Carbon::create(2020, 03, 01, 10),
                    'is_in_range' => true,
                ],
            ],
            'only end at not in range' => [
                [
                    'end_at' => Carbon::create(2020, 01, 01, 10),
                    'is_in_range' => false,
                ],
            ],
        ];
    }

    public static function dateBetweenFailProvider(): array
    {
        return [
            'in range' => [
                [
                    'start_at' => Carbon::create(2020, 02, 03, 10),
                    'end_at' => Carbon::create(2020, 02, 04, 10),
                    'is_in_range' => true,
                ],
            ],
            'not in range' => [
                [
                    'start_at' => Carbon::create(2020, 02, 01, 10),
                    'end_at' => Carbon::create(2020, 02, 03, 10),
                    'is_in_range' => false,
                ],
            ],
        ];
    }

    public static function timeBetweenPassProvider(): array
    {
        Carbon::setTestNow('2022-03-04T12:00:00');

        return [
            'in range' => [
                [
                    'start_at' => Carbon::now()->subHour()->toTimeString(),
                    'end_at' => Carbon::now()->addHour()->toTimeString(),
                    'is_in_range' => true,
                ],
            ],
            'not in range' => [
                [
                    'start_at' => Carbon::now()->addHour()->toTimeString(),
                    'end_at' => Carbon::now()->addHours(2)->toTimeString(),
                    'is_in_range' => false,
                ],
            ],
            'only start at in range' => [
                [
                    'start_at' => Carbon::now()->subHour()->toTimeString(),
                    'is_in_range' => true,
                ],
            ],
            'only start at not in range' => [
                [
                    'start_at' => Carbon::now()->addHour()->toTimeString(),
                    'is_in_range' => false,
                ],
            ],
            'only end at in range' => [
                [
                    'end_at' => Carbon::now()->addHour()->toTimeString(),
                    'is_in_range' => true,
                ],
            ],
            'only end at not in range' => [
                [
                    'end_at' => Carbon::now()->subHour()->toTimeString(),
                    'is_in_range' => false,
                ],
            ],
            'end at less in range' => [
                [
                    'start_at' => Carbon::now()->addHours(2)->toTimeString(),
                    'end_at' => Carbon::now()->addHour()->toTimeString(),
                    'is_in_range' => true,
                ],
            ],
            'end at less not in range' => [
                [
                    'start_at' => Carbon::now()->addHour()->toTimeString(),
                    'end_at' => Carbon::now()->subHour()->toTimeString(),
                    'is_in_range' => false,
                ],
            ],
        ];
    }

    public static function cartLengthProviderPass(): array
    {
        return [
            'min-max min value' => [
                1.0,
                2.0,
                [
                    'min_value' => 3,
                    'max_value' => 10,
                ],
            ],
            'min-max max value' => [
                2.0,
                3.0,
                [
                    'min_value' => 3,
                    'max_value' => 5,
                ],
            ],
            'only min value' => [
                2.0,
                3.0,
                [
                    'min_value' => 3,
                ],
            ],
            'only max value' => [
                2.0,
                2.0,
                [
                    'max_value' => 5,
                ],
            ],
        ];
    }

    public static function cartLengthProviderFail(): array
    {
        return [
            'min-max min value' => [
                1.0,
                2.0,
                [
                    'min_value' => 5,
                    'max_value' => 10,
                ],
            ],
            'min-max max value' => [
                3.0,
                3.0,
                [
                    'min_value' => 3,
                    'max_value' => 5,
                ],
            ],
            'only min value' => [
                2.0,
                3.0,
                [
                    'min_value' => 10,
                ],
            ],
            'only max value' => [
                4.0,
                5.0,
                [
                    'max_value' => 5,
                ],
            ],
        ];
    }

    public static function couponsCountProvider(): array
    {
        return [
            'pass min-max min value' => [
                3,
                [
                    'min_value' => 3,
                    'max_value' => 10,
                ],
                true,
            ],
            'pass min-max max value' => [
                3,
                [
                    'min_value' => 1,
                    'max_value' => 3,
                ],
                true,
            ],
            'pass only min value' => [
                3,
                [
                    'min_value' => 2,
                ],
                true,
            ],
            'pass only max value' => [
                2,
                [
                    'max_value' => 5,
                ],
                true,
            ],
            'fail min-max min value' => [
                4,
                [
                    'min_value' => 5,
                    'max_value' => 10,
                ],
                false,
            ],
            'fail min-max max value' => [
                6,
                [
                    'min_value' => 3,
                    'max_value' => 5,
                ],
                false,
            ],
            'fail only min value' => [
                2,
                [
                    'min_value' => 10,
                ],
                false,
            ],
            'fail only max value' => [
                6,
                [
                    'max_value' => 5,
                ],
                false,
            ],
        ];
    }

    public static function couponsCountWithSalesProvider(): array
    {
        return [
            'pass' => [true],
            'fail' => [false],
        ];
    }

    /**
     * @throws DtoException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->discount = Discount::factory()->create([
            'active' => true,
        ]);
        $this->conditionGroup = ConditionGroup::create();
        $this->shippingMethod = ShippingMethod::factory()->create();

        $this->currency = Currency::DEFAULT;
        $this->productService = App::make(ProductService::class);

        $this->product = $this->productService->create(FakeDto::productCreateDto([
            'prices_base' => [PriceDto::from(Money::of(20, $this->currency->value))],
        ]));

        $this->set = ProductSet::factory()->create();

        $this->discount->conditionGroups()->attach($this->conditionGroup);

        $this->discountService = App::make(DiscountServiceContract::class);
    }

    public function testCheckConditionGroupPass(): void
    {
        $this->prepareConditionGroup();

        $this->product->sets()->sync([$this->set->getKey()]);

        $cart = CartDto::fromArray([
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'items' => [
                [
                    'cartitem_id' => 0,
                    'product_id' => $this->product->getKey(),
                    'quantity' => 2,
                    'schemas' => [],
                ],
            ],
            'coupons' => [],
            'shipping_method_id' => $this->shippingMethod->getKey(),
        ]);

        $this->assertTrue(
            $this->discountService->checkConditionGroup(
                $this->conditionGroup,
                $cart,
                Money::of(40.0, $this->currency->value),
            )
        );
    }

    /**
     * @throws DtoException
     */
    public function testCheckConditionGroupFail(): void
    {
        $this->prepareConditionGroup();

        $cart = CartDto::fromArray([
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'items' => [
                [
                    'cartitem_id' => 0,
                    'product_id' => $this->product->getKey(),
                    'quantity' => 2,
                    'schemas' => [],
                ],
            ],
            'coupons' => [],
            'shipping_method_id' => $this->shippingMethod->getKey(),
        ]);

        $this->assertFalse(
            $this->discountService->checkConditionGroup(
                $this->conditionGroup,
                $cart,
                Money::of(40.0, $this->currency->value),
            )
        );
    }

    /**
     * @throws UnknownCurrencyException
     * @throws DtoException
     * @throws RoundingNecessaryException
     * @throws NumberFormatException
     */
    public function testCheckConditionGroupsPass(): void
    {
        $this->prepareConditionGroup();
        $this->discount->conditionGroups()->attach($this->prepareNewConditionGroup());

        $product = $this->productService->create(FakeDto::productCreateDto([
            'prices_base' => [PriceDto::from(Money::of(60, $this->currency->value))],
        ]));

        $product->sets()->sync([$this->set->getKey()]);

        $cart = CartDto::fromArray([
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'items' => [
                [
                    'cartitem_id' => 0,
                    'product_id' => $product->getKey(),
                    'quantity' => 2,
                    'schemas' => [],
                ],
            ],
            'coupons' => [],
            'shipping_method_id' => $this->shippingMethod->getKey(),
        ]);

        $this->assertTrue(
            $this->discountService->checkConditionGroups(
                $this->discount,
                $cart,
                Money::of(120.0, $this->currency->value),
            )
        );
    }

    /**
     * @throws UnknownCurrencyException
     * @throws DtoException
     * @throws RoundingNecessaryException
     * @throws NumberFormatException
     */
    public function testCheckConditionGroupsFail(): void
    {
        $this->prepareConditionGroup();
        $this->discount->conditionGroups()->attach($this->prepareNewConditionGroup());

        $product = $this->productService->create(FakeDto::productCreateDto([
            'prices_base' => [PriceDto::from(Money::of(60, $this->currency->value))],
        ]));

        $cart = CartDto::fromArray([
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'items' => [
                [
                    'cartitem_id' => 0,
                    'product_id' => $product->getKey(),
                    'quantity' => 2,
                    'schemas' => [],
                ],
            ],
            'coupons' => [],
            'shipping_method_id' => $this->shippingMethod->getKey(),
        ]);

        $this->assertFalse(
            $this->discountService->checkConditionGroups(
                $this->discount,
                $cart,
                Money::of(120.0, $this->currency->value),
            )
        );
    }

    public function testCheckConditionOrderValuePass(): void
    {
        $discountCondition = DiscountCondition::query()->create([
            'type' => ConditionType::ORDER_VALUE,
            'condition_group_id' => $this->conditionGroup->getKey(),
            'value' => [
                'include_taxes' => false,
                'is_in_range' => true,
            ],
        ]);
        $discountCondition->pricesMin()->create([
            'currency' => $this->currency->value,
            'value' => 999,
            'price_type' => DiscountConditionPriceType::PRICE_MIN->value,
        ]);
        $discountCondition->pricesMax()->create([
            'currency' => $this->currency->value,
            'value' => 9999,
            'price_type' => DiscountConditionPriceType::PRICE_MAX->value,
        ]);

        $this->assertTrue(
            $this->discountService->checkCondition(
                condition: $discountCondition,
                cartValue: Money::of(
                    50.0,
                    $this->currency->value
                ),
            )
        );
    }

    public function testCheckConditionOrderValueNotInRangePass(): void
    {
        $discountCondition = DiscountCondition::query()->create([
            'type' => ConditionType::ORDER_VALUE,
            'condition_group_id' => $this->conditionGroup->getKey(),
            'value' => [
                'include_taxes' => false,
                'is_in_range' => false,
            ],
        ]);
        $discountCondition->pricesMin()->create([
            'currency' => $this->currency->value,
            'value' => 999,
            'price_type' => DiscountConditionPriceType::PRICE_MIN->value,
        ]);
        $discountCondition->pricesMax()->create([
            'currency' => $this->currency->value,
            'value' => 9999,
            'price_type' => DiscountConditionPriceType::PRICE_MAX->value,
        ]);

        $this->assertTrue(
            $this->discountService->checkCondition(
                condition: $discountCondition,
                cartValue: Money::of(
                    100.0,
                    $this->currency->value
                ),
            )
        );
    }

    public function testCheckConditionOrderValueFail(): void
    {
        $discountCondition = DiscountCondition::query()->create([
            'type' => ConditionType::ORDER_VALUE,
            'condition_group_id' => $this->conditionGroup->getKey(),
            'value' => [
                'include_taxes' => false,
                'is_in_range' => true,
            ],
        ]);
        $discountCondition->pricesMin()->create([
            'currency' => $this->currency->value,
            'value' => 999,
            'price_type' => DiscountConditionPriceType::PRICE_MIN->value,
        ]);
        $discountCondition->pricesMax()->create([
            'currency' => $this->currency->value,
            'value' => 9999,
            'price_type' => DiscountConditionPriceType::PRICE_MAX->value,
        ]);

        $this->assertFalse(
            $this->discountService->checkCondition(
                condition: $discountCondition,
                cartValue: Money::of(
                    100.0,
                    $this->currency->value
                ),
            )
        );
    }

    public function testCheckConditionOrderValueNotInRangeFail(): void
    {
        $discountCondition = DiscountCondition::query()->create([
            'type' => ConditionType::ORDER_VALUE,
            'condition_group_id' => $this->conditionGroup->getKey(),
            'value' => [
                'include_taxes' => false,
                'is_in_range' => false,
            ],
        ]);
        $discountCondition->pricesMin()->create([
            'currency' => $this->currency->value,
            'value' => 999,
            'price_type' => DiscountConditionPriceType::PRICE_MIN->value,
        ]);
        $discountCondition->pricesMax()->create([
            'currency' => $this->currency->value,
            'value' => 9999,
            'price_type' => DiscountConditionPriceType::PRICE_MAX->value,
        ]);

        $this->assertFalse(
            $this->discountService->checkCondition(
                condition: $discountCondition,
                cartValue: Money::of(
                    50.0,
                    $this->currency->value
                ),
            )
        );
    }

    public function testCheckConditionUserInRolePass(): void
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'User role']);
        $user->assignRole($role);

        Auth::setUser($user);

        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::USER_IN_ROLE,
            'value' => [
                'roles' => [$role->getKey()],
                'is_allow_list' => true,
            ],
        ]);

        $this->assertTrue(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value))
        );
    }

    public function testCheckConditionUserInRoleAllowListFalsePass(): void
    {
        $user = User::factory()->create();
        $userRole = Role::create(['name' => 'User role']);
        $role = Role::create(['name' => 'Discount Role']);

        $user->assignRole($userRole);
        Auth::setUser($user);

        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::USER_IN_ROLE,
            'value' => [
                'roles' => [$role->getKey()],
                'is_allow_list' => false,
            ],
        ]);

        $this->assertTrue(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value))
        );
    }

    public function testCheckConditionUserInRoleFail(): void
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'User role']);

        Auth::setUser($user);

        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::USER_IN_ROLE,
            'value' => [
                'roles' => [$role->getKey()],
                'is_allow_list' => true,
            ],
        ]);

        $this->assertFalse(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value))
        );
    }

    public function testCheckConditionUserInRoleAllowListFalseFail(): void
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'User role']);
        $user->assignRole($role);

        Auth::setUser($user);

        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::USER_IN_ROLE,
            'value' => [
                'roles' => [$role->getKey()],
                'is_allow_list' => false,
            ],
        ]);

        $this->assertFalse(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value))
        );
    }

    public function testCheckConditionUserInPass(): void
    {
        $user = User::factory()->create();

        Auth::setUser($user);

        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::USER_IN,
            'value' => [
                'users' => [$user->getKey()],
                'is_allow_list' => true,
            ],
        ]);

        $this->assertTrue(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value))
        );
    }

    public function testCheckConditionUserInAllowListFalsePass(): void
    {
        $user = User::factory()->create();

        Auth::setUser($user);

        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::USER_IN,
            'value' => [
                'users' => [],
                'is_allow_list' => false,
            ],
        ]);

        $this->assertTrue(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value))
        );
    }

    public function testCheckConditionUserInFail(): void
    {
        $user = User::factory()->create();

        Auth::setUser($user);

        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::USER_IN,
            'value' => [
                'users' => [],
                'is_allow_list' => true,
            ],
        ]);

        $this->assertFalse(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value))
        );
    }

    public function testCheckConditionUserInAllowListFalseFail(): void
    {
        $user = User::factory()->create();

        Auth::setUser($user);

        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::USER_IN,
            'value' => [
                'users' => [$user->getKey()],
                'is_allow_list' => false,
            ],
        ]);

        $this->assertFalse(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value))
        );
    }

    /**
     * @throws DtoException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     */
    public function testCheckConditionProductInPass(): void
    {
        $product1 = $this->productService->create(FakeDto::productCreateDto());
        $product2 = $this->productService->create(FakeDto::productCreateDto());

        $cart = CartDto::fromArray([
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'items' => [
                [
                    'cartitem_id' => 0,
                    'product_id' => $product1->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
            ],
            'coupons' => [],
            'shipping_method_id' => $this->shippingMethod->getKey(),
        ]);

        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::PRODUCT_IN,
            'value' => [
                'products' => [
                    $product1->getKey(),
                    $product2->getKey(),
                ],
                'is_allow_list' => true,
            ],
        ]);

        $this->assertTrue(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value), $cart)
        );
    }

    /**
     * @throws DtoException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     */
    public function testCheckConditionProductInFail(): void
    {
        $product1 = $this->productService->create(FakeDto::productCreateDto());
        $product2 = $this->productService->create(FakeDto::productCreateDto());

        $cart = CartDto::fromArray([
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'items' => [
                [
                    'cartitem_id' => 0,
                    'product_id' => $product1->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
            ],
            'coupons' => [],
            'shipping_method_id' => $this->shippingMethod->getKey(),
        ]);

        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::PRODUCT_IN,
            'value' => [
                'products' => [
                    $product2->getKey(),
                ],
                'is_allow_list' => true,
            ],
        ]);

        $this->assertFalse(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value), $cart)
        );
    }

    /**
     * @throws DtoException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     */
    public function testCheckConditionProductInAllowListFalsePass(): void
    {
        $product1 = $this->productService->create(FakeDto::productCreateDto());
        $product2 = $this->productService->create(FakeDto::productCreateDto());

        $cart = CartDto::fromArray([
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'items' => [
                [
                    'cartitem_id' => 0,
                    'product_id' => $product1->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
            ],
            'coupons' => [],
            'shipping_method_id' => $this->shippingMethod->getKey(),
        ]);

        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::PRODUCT_IN,
            'value' => [
                'products' => [
                    $product2->getKey(),
                ],
                'is_allow_list' => false,
            ],
        ]);

        $this->assertTrue(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value), $cart)
        );
    }

    /**
     * @throws DtoException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     */
    public function testCheckConditionProductInAllowListFalseFail(): void
    {
        $this->productService->create(FakeDto::productCreateDto());
        $product2 = $this->productService->create(FakeDto::productCreateDto());

        $cart = CartDto::fromArray([
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'items' => [
                [
                    'cartitem_id' => 0,
                    'product_id' => $product2->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
            ],
            'coupons' => [],
            'shipping_method_id' => $this->shippingMethod->getKey(),
        ]);

        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::PRODUCT_IN,
            'value' => [
                'products' => [
                    $product2->getKey(),
                ],
                'is_allow_list' => false,
            ],
        ]);

        $this->assertFalse(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value), $cart)
        );
    }

    /**
     * @throws DtoException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     * @throws DtoException
     */
    public function testCheckConditionProductInSetPass(): void
    {
        $product = $this->productService->create(FakeDto::productCreateDto());
        $set1 = ProductSet::factory()->create();
        $set2 = ProductSet::factory()->create();

        $product->sets()->sync([$set1->getKey()]);

        $cart = CartDto::fromArray([
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'items' => [
                [
                    'cartitem_id' => 0,
                    'product_id' => $product->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
            ],
            'coupons' => [],
            'shipping_method_id' => $this->shippingMethod->getKey(),
        ]);

        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::PRODUCT_IN_SET,
            'value' => [
                'product_sets' => [
                    $set1->getKey(),
                    $set2->getKey(),
                ],
                'is_allow_list' => true,
            ],
        ]);

        $this->assertTrue(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value), $cart)
        );
    }

    /**
     * @throws RoundingNecessaryException
     * @throws DtoException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     */
    public function testCheckConditionProductInChildrenSetPass(): void
    {
        $product = $this->productService->create(FakeDto::productCreateDto());
        $set1 = ProductSet::factory()->create();
        $childrenSet = ProductSet::factory()->create([
            'parent_id' => $set1->getKey(),
        ]);
        $subChildrenSet = ProductSet::factory()->create([
            'parent_id' => $childrenSet->getKey(),
        ]);

        $product->sets()->sync([$subChildrenSet->getKey()]);

        $cart = CartDto::fromArray([
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'items' => [
                [
                    'cartitem_id' => 0,
                    'product_id' => $product->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
            ],
            'coupons' => [],
            'shipping_method_id' => $this->shippingMethod->getKey(),
        ]);

        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::PRODUCT_IN_SET,
            'value' => [
                'product_sets' => [
                    $set1->getKey(),
                ],
                'is_allow_list' => true,
            ],
        ]);

        $this->assertTrue(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value), $cart)
        );
    }

    /**
     * @throws DtoException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     */
    public function testCheckConditionProductInSetFail(): void
    {
        $product = $this->productService->create(FakeDto::productCreateDto());
        $set1 = ProductSet::factory()->create();
        $set2 = ProductSet::factory()->create();

        $product->sets()->sync([$set1->getKey()]);

        $cart = CartDto::fromArray([
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'items' => [
                [
                    'cartitem_id' => 0,
                    'product_id' => $product->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
            ],
            'coupons' => [],
            'shipping_method_id' => $this->shippingMethod->getKey(),
        ]);

        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::PRODUCT_IN_SET,
            'value' => [
                'product_sets' => [
                    $set2->getKey(),
                ],
                'is_allow_list' => true,
            ],
        ]);

        $this->assertFalse(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value), $cart)
        );
    }

    /**
     * @throws DtoException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     */
    public function testCheckConditionProductInChildrenSetFail(): void
    {
        $product = $this->productService->create(FakeDto::productCreateDto());
        $set1 = ProductSet::factory()->create();
        $childrenSet = ProductSet::factory()->create([
            'parent_id' => $set1->getKey(),
        ]);
        $subChildrenSet = ProductSet::factory()->create([
            'parent_id' => $childrenSet->getKey(),
        ]);
        $set2 = ProductSet::factory()->create();

        $product->sets()->sync([$subChildrenSet->getKey()]);

        $cart = CartDto::fromArray([
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'items' => [
                [
                    'cartitem_id' => 0,
                    'product_id' => $product->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
            ],
            'coupons' => [],
            'shipping_method_id' => $this->shippingMethod->getKey(),
        ]);

        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::PRODUCT_IN_SET,
            'value' => [
                'product_sets' => [
                    $set2->getKey(),
                ],
                'is_allow_list' => true,
            ],
        ]);

        $this->assertFalse(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value), $cart)
        );
    }

    /**
     * @throws DtoException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     */
    public function testCheckConditionProductInSetAllowListFalsePass(): void
    {
        $product = $this->productService->create(FakeDto::productCreateDto());
        $set1 = ProductSet::factory()->create();
        $set2 = ProductSet::factory()->create();

        $product->sets()->sync([$set1->getKey()]);

        $cart = CartDto::fromArray([
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'items' => [
                [
                    'cartitem_id' => 0,
                    'product_id' => $product->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
            ],
            'coupons' => [],
            'shipping_method_id' => $this->shippingMethod->getKey(),
        ]);

        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::PRODUCT_IN_SET,
            'value' => [
                'product_sets' => [
                    $set2->getKey(),
                ],
                'is_allow_list' => false,
            ],
        ]);

        $this->assertTrue(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value), $cart)
        );
    }

    /**
     * @throws DtoException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     */
    public function testCheckConditionProductInChildrenSetAllowListFalsePass(): void
    {
        $product = $this->productService->create(FakeDto::productCreateDto());
        $set1 = ProductSet::factory()->create();
        $childrenSet = ProductSet::factory()->create([
            'parent_id' => $set1->getKey(),
        ]);
        $subChildrenSet = ProductSet::factory()->create([
            'parent_id' => $childrenSet->getKey(),
        ]);
        $set2 = ProductSet::factory()->create();

        $product->sets()->sync([$subChildrenSet->getKey()]);

        $cart = CartDto::fromArray([
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'items' => [
                [
                    'cartitem_id' => 0,
                    'product_id' => $product->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
            ],
            'coupons' => [],
            'shipping_method_id' => $this->shippingMethod->getKey(),
        ]);

        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::PRODUCT_IN_SET,
            'value' => [
                'product_sets' => [
                    $set2->getKey(),
                ],
                'is_allow_list' => false,
            ],
        ]);

        $this->assertTrue(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value), $cart)
        );
    }

    /**
     * @throws DtoException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     */
    public function testCheckConditionProductInSetAllowListFalseFail(): void
    {
        $product = $this->productService->create(FakeDto::productCreateDto());
        ProductSet::factory()->create();
        $set2 = ProductSet::factory()->create();

        $product->sets()->sync([$set2->getKey()]);

        $cart = CartDto::fromArray([
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'items' => [
                [
                    'cartitem_id' => 0,
                    'product_id' => $product->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
            ],
            'coupons' => [],
            'shipping_method_id' => $this->shippingMethod->getKey(),
        ]);

        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::PRODUCT_IN_SET,
            'value' => [
                'product_sets' => [
                    $set2->getKey(),
                ],
                'is_allow_list' => false,
            ],
        ]);

        $this->assertFalse(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value), $cart)
        );
    }

    /**
     * @throws DtoException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     */
    public function testCheckConditionProductInChildrenSetAllowListFalseFail(): void
    {
        $product = $this->productService->create(FakeDto::productCreateDto());
        $set1 = ProductSet::factory()->create();
        $childrenSet = ProductSet::factory()->create([
            'parent_id' => $set1->getKey(),
        ]);
        $subChildrenSet = ProductSet::factory()->create([
            'parent_id' => $childrenSet->getKey(),
        ]);

        $product->sets()->sync([$subChildrenSet->getKey()]);

        $cart = CartDto::fromArray([
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'items' => [
                [
                    'cartitem_id' => 0,
                    'product_id' => $product->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
            ],
            'coupons' => [],
            'shipping_method_id' => $this->shippingMethod->getKey(),
        ]);

        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::PRODUCT_IN_SET,
            'value' => [
                'product_sets' => [
                    $set1->getKey(),
                ],
                'is_allow_list' => false,
            ],
        ]);

        $this->assertFalse(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value), $cart)
        );
    }

    /**
     * @dataProvider dateBetweenPassProvider
     */
    public function testCheckConditionDateBetweenPass($value): void
    {
        $this->travelTo(Carbon::create(2020, 02, 02, 10));

        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::DATE_BETWEEN,
            'value' => $value,
        ]);

        $this->assertTrue(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value))
        );
    }

    /**
     * @dataProvider dateBetweenFailProvider
     */
    public function testCheckConditionDateBetweenNotInRangePass($value): void
    {
        $this->travelTo(Carbon::create(2020, 02, 02, 10));

        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::DATE_BETWEEN,
            'value' => $value,
        ]);

        $this->assertFalse(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value))
        );
    }

    /**
     * @dataProvider timeBetweenPassProvider
     */
    public function testCheckConditionTimeBetweenPass($value): void
    {
        Carbon::setTestNow('2022-03-04T12:00:00');

        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::TIME_BETWEEN,
            'value' => $value,
        ]);

        $this->assertTrue(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value))
        );
    }

    public function testCheckConditionMaxUsesPass(): void
    {
        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::MAX_USES,
            'value' => [
                'max_uses' => 100,
            ],
        ]);

        $this->assertTrue(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value))
        );
    }

    public function testCheckConditionMaxUsesFail(): void
    {
        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::MAX_USES,
            'value' => [
                'max_uses' => 0,
            ],
        ]);

        $this->assertFalse(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value))
        );
    }

    public function testCheckConditionMaxUsesPerUserPass(): void
    {
        Auth::setUser(User::factory()->create());
        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::MAX_USES_PER_USER,
            'value' => [
                'max_uses' => 100,
            ],
        ]);

        $this->assertTrue(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value))
        );
    }

    public function testCheckConditionMaxUsesPerUserFail(): void
    {
        Auth::setUser(User::factory()->create());
        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::MAX_USES_PER_USER,
            'value' => [
                'max_uses' => 0,
            ],
        ]);

        $this->assertFalse(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value))
        );
    }

    public function testCheckConditionMaxUsesPerUserNoAuthUser(): void
    {
        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::MAX_USES_PER_USER,
            'value' => [
                'max_uses' => 100,
            ],
        ]);

        $this->assertFalse(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value))
        );
    }

    public function testCheckConditionWeekdayInPass(): void
    {
        Carbon::setTestNow('2022-03-04T12:00:00'); // piątek

        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::WEEKDAY_IN,
            'value' => [
                'weekday' => [false, false, false, false, false, true, false],
            ],
        ]);

        $this->assertTrue(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value))
        );
    }

    public function testCheckConditionWeekdayInFail(): void
    {
        Carbon::setTestNow('2022-03-04T12:00:00'); // piątek

        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::WEEKDAY_IN,
            'value' => [
                'weekday' => [true, true, true, true, true, false, true],
            ],
        ]);

        $this->assertFalse(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value))
        );
    }

    /**
     * @dataProvider cartLengthProviderPass
     *
     * @throws DtoException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     */
    public function testCheckConditionCartLengthPass($quantity1, $quantity2, $value): void
    {
        $product1 = $this->productService->create(FakeDto::productCreateDto());
        $product2 = $this->productService->create(FakeDto::productCreateDto());

        $cart = CartDto::fromArray([
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'items' => [
                [
                    'cartitem_id' => 0,
                    'product_id' => $product1->getKey(),
                    'quantity' => $quantity1,
                    'schemas' => [],
                ],
                [
                    'cartitem_id' => 1,
                    'product_id' => $product2->getKey(),
                    'quantity' => $quantity2,
                    'schemas' => [],
                ],
            ],
            'coupons' => [],
            'shipping_method_id' => $this->shippingMethod->getKey(),
        ]);

        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::CART_LENGTH,
            'value' => $value,
        ]);

        $this->assertTrue($cart->getCartLength() === $quantity1 + $quantity2);
        $this->assertTrue(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value), $cart)
        );
    }

    /**
     * @dataProvider cartLengthProviderFail
     *
     * @throws DtoException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     */
    public function testCheckConditionCartLengthFail($quantity1, $quantity2, $value): void
    {
        $product1 = $this->productService->create(FakeDto::productCreateDto());
        $product2 = $this->productService->create(FakeDto::productCreateDto());

        $cart = CartDto::fromArray([
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'items' => [
                [
                    'cartitem_id' => 0,
                    'product_id' => $product1->getKey(),
                    'quantity' => $quantity1,
                    'schemas' => [],
                ],
                [
                    'cartitem_id' => 1,
                    'product_id' => $product2->getKey(),
                    'quantity' => $quantity2,
                    'schemas' => [],
                ],
            ],
            'coupons' => [],
            'shipping_method_id' => $this->shippingMethod->getKey(),
        ]);

        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::CART_LENGTH,
            'value' => $value,
        ]);

        $this->assertTrue($cart->getCartLength() === $quantity1 + $quantity2);
        $this->assertFalse(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value), $cart)
        );
    }

    /**
     * @dataProvider couponsCountProvider
     *
     * @throws DtoException
     */
    public function testCheckConditionCouponsCount($quantity, $value, $result): void
    {
        $product1 = $this->productService->create(FakeDto::productCreateDto());

        $coupons = Discount::factory()->count($quantity)->create();

        $cart = CartDto::fromArray([
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'items' => [
                [
                    'cartitem_id' => 0,
                    'product_id' => $product1->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
            ],
            'coupons' => Arr::pluck($coupons, 'code'),
            'shipping_method_id' => $this->shippingMethod->getKey(),
        ]);

        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::COUPONS_COUNT,
            'value' => $value,
        ]);

        $this->assertTrue(count($cart->getCoupons()) === $quantity);
        $this->assertTrue(
            $this->discountService->checkCondition(
                $discountCondition,
                Money::zero($this->currency->value),
                $cart
            ) === $result
        );
    }

    /**
     * @dataProvider couponsCountWithSalesProvider
     *
     * @throws DtoException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     */
    public function testCheckConditionCouponsCountWithSales($result): void
    {
        $product1 = $this->productService->create(FakeDto::productCreateDto());

        Discount::factory()->create(['code' => null]);

        $cart = CartDto::fromArray([
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'items' => [
                [
                    'cartitem_id' => 0,
                    'product_id' => $product1->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
            ],
            'coupons' => [],
            'shipping_method_id' => $this->shippingMethod->getKey(),
        ]);

        $value = $result ? ['max_value' => 0] : ['min_value' => 1];

        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::COUPONS_COUNT,
            'value' => $value,
        ]);

        $this->assertEmpty($cart->getCoupons());
        $this->assertTrue(
            $this->discountService->checkCondition(
                $discountCondition,
                Money::zero($this->currency->value),
                $cart
            ) === $result
        );
    }

    private function prepareConditionGroup(): void
    {
        /** @var DiscountCondition $condition */
        $condition = DiscountCondition::query()->create([
            'type' => ConditionType::ORDER_VALUE,
            'condition_group_id' => $this->conditionGroup->getKey(),
            'value' => [
                'include_taxes' => false,
                'is_in_range' => true,
                /*
                'min_values' => [
                    [
                        'value' => "9.99",
                        'currency' => $this->currency->value,
                    ]
                ],
                'max_values' => [
                    [
                        'value' => "99.99",
                        'currency' => $this->currency->value,
                    ]
                ],
                */
            ],
        ]);
        $condition->pricesMin()->create([
            'currency' => $this->currency->value,
            'value' => 999,
            'price_type' => DiscountConditionPriceType::PRICE_MIN->value,
        ]);
        $condition->pricesMax()->create([
            'currency' => $this->currency->value,
            'value' => 9999,
            'price_type' => DiscountConditionPriceType::PRICE_MAX->value,
        ]);

        $this->conditionGroup->conditions()->create([
            'type' => ConditionType::PRODUCT_IN,
            'value' => [
                'products' => [
                    $this->product->getKey(),
                ],
                'is_allow_list' => true,
            ],
        ]);

        $this->conditionGroup->conditions()->create([
            'type' => ConditionType::PRODUCT_IN_SET,
            'value' => [
                'product_sets' => [
                    $this->set->getKey(),
                ],
                'is_allow_list' => true,
            ],
        ]);
    }

    private function prepareNewConditionGroup(): ConditionGroup
    {
        /** @var ConditionGroup $conditionGroup */
        $conditionGroup = ConditionGroup::query()->create();

        /** @var DiscountCondition $condition */
        $condition = DiscountCondition::query()->create([
            'type' => ConditionType::ORDER_VALUE,
            'condition_group_id' => $conditionGroup->getKey(),
            'value' => [
                'include_taxes' => false,
                'is_in_range' => true,
            ],
        ]);
        $condition->pricesMin()->create([
            'currency' => $this->currency->value,
            'value' => 10000,
            'price_type' => DiscountConditionPriceType::PRICE_MIN->value,
        ]);
        $condition->pricesMax()->create([
            'currency' => $this->currency->value,
            'value' => 19999,
            'price_type' => DiscountConditionPriceType::PRICE_MAX->value,
        ]);

        $conditionGroup->conditions()->create([
            'type' => ConditionType::PRODUCT_IN_SET,
            'value' => [
                'product_sets' => [
                    $this->set->getKey(),
                ],
                'is_allow_list' => true,
            ],
        ]);

        return $conditionGroup;
    }
}
