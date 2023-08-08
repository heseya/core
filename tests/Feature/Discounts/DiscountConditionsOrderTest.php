<?php

namespace Tests\Feature\Discounts;

use App\Enums\ConditionType;
use App\Enums\DiscountTargetType;
use App\Enums\DiscountType;
use App\Models\ConditionGroup;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class DiscountConditionsOrderTest extends TestCase
{
    use RefreshDatabase;

    private ConditionGroup $conditionGroup;
    private $shippingMethod;
    private $product;
    protected array $items;
    protected array $address;

    public function setUp(): void
    {
        parent::setUp();

        $this->conditionGroup = ConditionGroup::create();
        $this->shippingMethod = ShippingMethod::factory()->create();

        $this->product = Product::factory()->create([
            'price' => 100.0,
            'public' => true,
        ]);

        $this->items = [
            [
                'product_id' => $this->product->getKey(),
                'quantity' => 1,
            ],
        ];

        $this->address = [
            'name' => 'Test User',
            'address' => 'Gdańska 89/1',
            'zip' => '85-022',
            'city' => 'Bydgoszcz',
            'phone' => '+48123123123',
            'country' => 'PL',
        ];
    }

    /**
     * @dataProvider authProvider
     */
    public function testDecrementingMaxUsesCondition($user): void
    {
        $this->$user->givePermissionTo('orders.add');

        $discount = Discount::factory()->create([
            'type' => DiscountType::PERCENTAGE,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'value' => 15,
        ]);

        $discount->conditionGroups()->attach($this->conditionGroup);
        $this->conditionGroup->conditions()->create([
            'type' => ConditionType::MAX_USES,
            'value' => [
                'max_uses' => 1,
            ],
        ]);

        $this->actingAs($this->$user)->postJson('/orders', [
            'email' => 'info@example.com',
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'billing_address' => $this->address,
            'shipping_place' => $this->address,
            'items' => $this->items,
            'coupons' => [
                $discount->code,
            ],
        ])->assertCreated();

        $condition = $this->conditionGroup->conditions()->first();
        $condition->refresh();
        $this->assertEquals(['max_uses' => 0], $condition->value);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDecrementingMaxUsesConditionLimitReached($user): void
    {
        $this->$user->givePermissionTo('orders.add');

        $discount = Discount::factory()->create([
            'type' => DiscountType::PERCENTAGE,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'value' => 15,
        ]);

        $discount->conditionGroups()->attach($this->conditionGroup);
        $this->conditionGroup->conditions()->create([
            'type' => ConditionType::MAX_USES,
            'value' => [
                'max_uses' => 0,
            ],
        ]);

        $this->actingAs($this->$user)->postJson('/orders', [
            'email' => 'info@example.com',
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'billing_address' => $this->address,
            'shipping_place' => $this->address,
            'items' => $this->items,
            'coupons' => [
                $discount->code,
            ],
        ])->assertUnprocessable();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDecrementingMaxUsesPerUserCondition($user): void
    {
        $this->$user->givePermissionTo('orders.add');

        $discount = Discount::factory()->create([
            'type' => DiscountType::PERCENTAGE,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'value' => 15,
        ]);

        $discount->conditionGroups()->attach($this->conditionGroup);
        $this->conditionGroup->conditions()->create([
            'type' => ConditionType::MAX_USES_PER_USER,
            'value' => [
                'max_uses' => 2,
            ],
        ]);

        $this->actingAs($this->$user)->postJson('/orders', [
            'email' => 'info@example.com',
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'billing_address' => $this->address,
            'shipping_place' => $this->address,
            'items' => $this->items,
            'coupons' => [
                $discount->code,
            ],
        ])->assertCreated();

        $condition = $this->conditionGroup->conditions()->first();
        $condition->refresh();
        $this->assertEquals(['max_uses' => 1], $condition->value);

        $this->actingAs($this->$user)->postJson('/orders', [
            'email' => 'info@example.com',
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'billing_address' => $this->address,
            'shipping_place' => $this->address,
            'items' => $this->items,
            'coupons' => [
                $discount->code,
            ],
        ])->assertUnprocessable();

        $otherUser = $this->user = User::factory()->create();
        $otherUser->givePermissionTo('orders.add');
        Auth::setUser($otherUser);

        $this->actingAs($otherUser)->postJson('/orders', [
            'email' => 'info@example.com',
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'billing_address' => $this->address,
            'shipping_place' => $this->address,
            'items' => $this->items,
            'coupons' => [
                $discount->code,
            ],
        ])->assertCreated();

        $condition->refresh();
        $this->assertEquals(['max_uses' => 0], $condition->value);
    }
}
