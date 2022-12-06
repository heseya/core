<?php

namespace Unit;

use App\Dtos\CartDto;
use App\Enums\ConditionType;
use App\Models\ConditionGroup;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductSet;
use App\Models\Role;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\Contracts\DiscountServiceContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class DiscountConditionsCheckTest extends TestCase
{
    use RefreshDatabase;

    private DiscountServiceContract $discountService;
    private ConditionGroup $conditionGroup;
    private $discount;
    private $shippingMethod;
    private $product;
    private $set;

    public function setUp(): void
    {
        parent::setUp();

        $this->discount = Discount::factory()->create([
            'active' => true,
        ]);
        $this->conditionGroup = ConditionGroup::create();
        $this->shippingMethod = ShippingMethod::factory()->create();

        $this->product = Product::factory()->create([
            'price' => 20.0,
            'public' => true,
        ]);

        $this->set = ProductSet::factory()->create();

        $this->discount->conditionGroups()->attach($this->conditionGroup);

        $this->discountService = App::make(DiscountServiceContract::class);
    }

    public function testCheckConditionGroupPass(): void
    {
        $this->prepareConditionGroup();

        $this->product->sets()->sync([$this->set->getKey()]);

        $cart = CartDto::fromArray([
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
                40.0,
            )
        );
    }

    public function testCheckConditionGroupFail(): void
    {
        $this->prepareConditionGroup();

        $cart = CartDto::fromArray([
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
                40.0,
            )
        );
    }

    public function testCheckConditionGroupsPass(): void
    {
        $this->prepareConditionGroup();
        $this->discount->conditionGroups()->attach($this->prepareNewConditionGroup());

        $product = Product::factory()->create([
            'price' => 60.0,
            'public' => true,
        ]);

        $product->sets()->sync([$this->set->getKey()]);

        $cart = CartDto::fromArray([
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
                120.0,
            )
        );
    }

    public function testCheckConditionGroupsFail(): void
    {
        $this->prepareConditionGroup();
        $this->discount->conditionGroups()->attach($this->prepareNewConditionGroup());

        $product = Product::factory()->create([
            'price' => 60.0,
            'public' => true,
        ]);

        $cart = CartDto::fromArray([
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
                120.0,
            )
        );
    }

    public function testCheckConditionOrderValuePass(): void
    {
        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::ORDER_VALUE,
            'value' => [
                'min_value' => 9.99,
                'max_value' => 99.99,
                'include_taxes' => false,
                'is_in_range' => true,
            ],
        ]);

        $this->assertTrue($this->discountService->checkCondition(condition: $discountCondition, cartValue: 50.00));
    }

    public function testCheckConditionOrderValueNotInRangePass(): void
    {
        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::ORDER_VALUE,
            'value' => [
                'min_value' => 9.99,
                'max_value' => 99.99,
                'include_taxes' => false,
                'is_in_range' => false,
            ],
        ]);

        $this->assertTrue($this->discountService->checkCondition(condition: $discountCondition, cartValue: 100.00));
    }

    public function testCheckConditionOrderValueFail(): void
    {
        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::ORDER_VALUE,
            'value' => [
                'min_value' => 9.99,
                'max_value' => 99.99,
                'include_taxes' => false,
                'is_in_range' => true,
            ],
        ]);

        $this->assertFalse($this->discountService->checkCondition(condition: $discountCondition, cartValue: 100.00));
    }

    public function testCheckConditionOrderValueNotInRangeFail(): void
    {
        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::ORDER_VALUE,
            'value' => [
                'min_value' => 9.99,
                'max_value' => 99.99,
                'include_taxes' => false,
                'is_in_range' => false,
            ],
        ]);

        $this->assertFalse($this->discountService->checkCondition(condition: $discountCondition, cartValue: 50.00));
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

        $this->assertTrue($this->discountService->checkCondition($discountCondition));
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

        $this->assertTrue($this->discountService->checkCondition($discountCondition));
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

        $this->assertFalse($this->discountService->checkCondition($discountCondition));
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

        $this->assertFalse($this->discountService->checkCondition($discountCondition));
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

        $this->assertTrue($this->discountService->checkCondition($discountCondition));
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

        $this->assertTrue($this->discountService->checkCondition($discountCondition));
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

        $this->assertFalse($this->discountService->checkCondition($discountCondition));
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

        $this->assertFalse($this->discountService->checkCondition($discountCondition));
    }

    public function testCheckConditionProductInPass(): void
    {
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        $cart = CartDto::fromArray([
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

        $this->assertTrue($this->discountService->checkCondition($discountCondition, $cart));
    }

    public function testCheckConditionProductInFail(): void
    {
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        $cart = CartDto::fromArray([
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

        $this->assertFalse($this->discountService->checkCondition($discountCondition, $cart));
    }

    public function testCheckConditionProductInAllowListFalsePass(): void
    {
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        $cart = CartDto::fromArray([
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

        $this->assertTrue($this->discountService->checkCondition($discountCondition, $cart));
    }

    public function testCheckConditionProductInAllowListFalseFail(): void
    {
        Product::factory()->create();
        $product2 = Product::factory()->create();

        $cart = CartDto::fromArray([
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

        $this->assertFalse($this->discountService->checkCondition($discountCondition, $cart));
    }

    public function testCheckConditionProductInSetPass(): void
    {
        $product = Product::factory()->create();
        $set1 = ProductSet::factory()->create();
        $set2 = ProductSet::factory()->create();

        $product->sets()->sync([$set1->getKey()]);

        $cart = CartDto::fromArray([
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

        $this->assertTrue($this->discountService->checkCondition($discountCondition, $cart));
    }

    public function testCheckConditionProductInChildrenSetPass(): void
    {
        $product = Product::factory()->create();
        $set1 = ProductSet::factory()->create();
        $childrenSet = ProductSet::factory()->create([
            'parent_id' => $set1->getKey(),
        ]);
        $subChildrenSet = ProductSet::factory()->create([
            'parent_id' => $childrenSet->getKey(),
        ]);

        $product->sets()->sync([$subChildrenSet->getKey()]);

        $cart = CartDto::fromArray([
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

        $this->assertTrue($this->discountService->checkCondition($discountCondition, $cart));
    }

    public function testCheckConditionProductInSetFail(): void
    {
        $product = Product::factory()->create();
        $set1 = ProductSet::factory()->create();
        $set2 = ProductSet::factory()->create();

        $product->sets()->sync([$set1->getKey()]);

        $cart = CartDto::fromArray([
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

        $this->assertFalse($this->discountService->checkCondition($discountCondition, $cart));
    }

    public function testCheckConditionProductInChildrenSetFail(): void
    {
        $product = Product::factory()->create();
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

        $this->assertFalse($this->discountService->checkCondition($discountCondition, $cart));
    }

    public function testCheckConditionProductInSetAllowListFalsePass(): void
    {
        $product = Product::factory()->create();
        $set1 = ProductSet::factory()->create();
        $set2 = ProductSet::factory()->create();

        $product->sets()->sync([$set1->getKey()]);

        $cart = CartDto::fromArray([
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

        $this->assertTrue($this->discountService->checkCondition($discountCondition, $cart));
    }

    public function testCheckConditionProductInChildrenSetAllowListFalsePass(): void
    {
        $product = Product::factory()->create();
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

        $this->assertTrue($this->discountService->checkCondition($discountCondition, $cart));
    }

    public function testCheckConditionProductInSetAllowListFalseFail(): void
    {
        $product = Product::factory()->create();
        ProductSet::factory()->create();
        $set2 = ProductSet::factory()->create();

        $product->sets()->sync([$set2->getKey()]);

        $cart = CartDto::fromArray([
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

        $this->assertFalse($this->discountService->checkCondition($discountCondition, $cart));
    }

    public function testCheckConditionProductInChildrenSetAllowListFalseFail(): void
    {
        $product = Product::factory()->create();
        $set1 = ProductSet::factory()->create();
        $childrenSet = ProductSet::factory()->create([
            'parent_id' => $set1->getKey(),
        ]);
        $subChildrenSet = ProductSet::factory()->create([
            'parent_id' => $childrenSet->getKey(),
        ]);

        $product->sets()->sync([$subChildrenSet->getKey()]);

        $cart = CartDto::fromArray([
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

        $this->assertFalse($this->discountService->checkCondition($discountCondition, $cart));
    }

    public function dateBetweenPassProvider(): array
    {
        return [
            'in range' => [
                [
                    'start_at' => Carbon::yesterday(),
                    'end_at' => Carbon::tomorrow(),
                    'is_in_range' => true,
                ],
            ],
            'not in range' => [
                [
                    'start_at' => Carbon::tomorrow(),
                    'end_at' => Carbon::tomorrow()->addDay(),
                    'is_in_range' => false,
                ],
            ],
            'only start at in range' => [
                [
                    'start_at' => Carbon::yesterday(),
                    'is_in_range' => true,
                ],
            ],
            'only start at not in range' => [
                [
                    'start_at' => Carbon::tomorrow(),
                    'is_in_range' => false,
                ],
            ],
            'only end at in range' => [
                [
                    'end_at' => Carbon::tomorrow(),
                    'is_in_range' => true,
                ],
            ],
            'only end at not in range' => [
                [
                    'end_at' => Carbon::yesterday(),
                    'is_in_range' => false,
                ],
            ],
            'in range start at equal end at' => [
                [
                    'start_at' => Carbon::today()->format('Y-m-d'),
                    'end_at' => Carbon::today()->format('Y-m-d'),
                    'is_in_range' => true,
                ],
            ],
            'not in range start at equal end at' => [
                [
                    'start_at' => Carbon::yesterday(),
                    'end_at' => Carbon::yesterday(),
                    'is_in_range' => false,
                ],
            ],
        ];
    }

    /**
     * @dataProvider dateBetweenPassProvider
     */
    public function testCheckConditionDateBetweenPass($value): void
    {
        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::DATE_BETWEEN,
            'value' => $value,
        ]);

        $this->assertTrue($this->discountService->checkCondition($discountCondition));
    }

    public function dateBetweenFailProvider(): array
    {
        return [
            'in range' => [
                [
                    'start_at' => Carbon::tomorrow(),
                    'end_at' => Carbon::tomorrow()->addDay(),
                    'is_in_range' => true,
                ],
            ],
            'not in range' => [
                [
                    'start_at' => Carbon::yesterday(),
                    'end_at' => Carbon::tomorrow(),
                    'is_in_range' => false,
                ],
            ],
            'only start at in range' => [
                [
                    'start_at' => Carbon::tomorrow(),
                    'is_in_range' => true,
                ],
            ],
            'only start at not in range' => [
                [
                    'start_at' => Carbon::yesterday(),
                    'is_in_range' => false,
                ],
            ],
            'only end at in range' => [
                [
                    'end_at' => Carbon::yesterday(),
                    'is_in_range' => true,
                ],
            ],
            'only end at not in range' => [
                [
                    'end_at' => Carbon::tomorrow(),
                    'is_in_range' => false,
                ],
            ],
            'in range start at equal end at' => [
                [
                    'start_at' => Carbon::tomorrow(),
                    'end_at' => Carbon::tomorrow(),
                    'is_in_range' => true,
                ],
            ],
            'not in range start at equal end at' => [
                [
                    'start_at' => Carbon::today()->format('Y-m-d'),
                    'end_at' => Carbon::today()->format('Y-m-d'),
                    'is_in_range' => false,
                ],
            ],
        ];
    }

    /**
     * @dataProvider dateBetweenFailProvider
     */
    public function testCheckConditionDateBetweenNotInRangePass($value): void
    {
        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::DATE_BETWEEN,
            'value' => $value,
        ]);

        $this->assertFalse($this->discountService->checkCondition($discountCondition));
    }

    public function timeBetweenPassProvider(): array
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

        $this->assertTrue($this->discountService->checkCondition($discountCondition));
    }

    public function testCheckConditionMaxUsesPass(): void
    {
        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::MAX_USES,
            'value' => [
                'max_uses' => 100,
            ],
        ]);

        $this->assertTrue($this->discountService->checkCondition($discountCondition));
    }

    public function testCheckConditionMaxUsesFail(): void
    {
        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::MAX_USES,
            'value' => [
                'max_uses' => 0,
            ],
        ]);

        $this->assertFalse($this->discountService->checkCondition($discountCondition));
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

        $this->assertTrue($this->discountService->checkCondition($discountCondition));
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

        $this->assertFalse($this->discountService->checkCondition($discountCondition));
    }

    public function testCheckConditionMaxUsesPerUserNoAuthUser(): void
    {
        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::MAX_USES_PER_USER,
            'value' => [
                'max_uses' => 100,
            ],
        ]);

        $this->assertFalse($this->discountService->checkCondition($discountCondition));
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

        $this->assertTrue($this->discountService->checkCondition($discountCondition));
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

        $this->assertFalse($this->discountService->checkCondition($discountCondition));
    }

    public function cartLengthProviderPass(): array
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

    /**
     * @dataProvider cartLengthProviderPass
     */
    public function testCheckConditionCartLengthPass($quantity1, $quantity2, $value): void
    {
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        $cart = CartDto::fromArray([
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
        $this->assertTrue($this->discountService->checkCondition($discountCondition, $cart));
    }

    public function cartLengthProviderFail(): array
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

    /**
     * @dataProvider cartLengthProviderFail
     */
    public function testCheckConditionCartLengthFail($quantity1, $quantity2, $value): void
    {
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        $cart = CartDto::fromArray([
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
        $this->assertFalse($this->discountService->checkCondition($discountCondition, $cart));
    }

    public function couponsCountProvider(): array
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

    /**
     * @dataProvider couponsCountProvider
     */
    public function testCheckConditionCouponsCount($quantity, $value, $result): void
    {
        $product1 = Product::factory()->create();

        $coupons = Discount::factory()->count($quantity)->create();

        $cart = CartDto::fromArray([
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
        $this->assertTrue($this->discountService->checkCondition($discountCondition, $cart) === $result);
    }

    public function couponsCountWithSalesProvider(): array
    {
        return [
            'pass' => [true],
            'fail' => [false],
        ];
    }

    /**
     * @dataProvider couponsCountWithSalesProvider
     */
    public function testCheckConditionCouponsCountWithSales($result): void
    {
        $product1 = Product::factory()->create();

        Discount::factory()->create(['code' => null]);

        $cart = CartDto::fromArray([
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

        $this->assertTrue(count($cart->getCoupons()) === 0);
        $this->assertTrue($this->discountService->checkCondition($discountCondition, $cart) === $result);
    }

    private function prepareConditionGroup(): void
    {
        $this->conditionGroup->conditions()->create([
            'type' => ConditionType::ORDER_VALUE,
            'value' => [
                'min_value' => 9.99,
                'max_value' => 99.99,
                'include_taxes' => false,
                'is_in_range' => true,
            ],
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
        $conditionGroup = ConditionGroup::create();

        $conditionGroup->conditions()->create([
            'type' => ConditionType::ORDER_VALUE,
            'value' => [
                'min_value' => 100.0,
                'max_value' => 199.99,
                'include_taxes' => false,
                'is_in_range' => true,
            ],
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
