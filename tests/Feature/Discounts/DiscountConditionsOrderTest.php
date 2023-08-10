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
    private Discount $discount;
    private $shippingMethod;
    private $product;
    private array $items;
    private array $address;

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

        $this->discount = Discount::factory()->create([
            'code' => null,
            'value' => 10,
            'type' => DiscountType::PERCENTAGE,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCountingMaxUsesCondition($user): void
    {
        $this->discount->refresh();
        $this->assertEquals(0, $this->discount->uses);

        $this->$user->givePermissionTo('orders.add');

        $this->discount->products()->attach($this->product->getKey());
        $this->discount->conditionGroups()->attach($this->conditionGroup);

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
        ])->assertCreated();

        $this->discount->refresh();
        $this->assertEquals(1, $this->discount->uses);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCountingMaxUsesConditionLimitReached($user): void
    {
        $this->discount->refresh();
        $this->assertEquals(0, $this->discount->uses);

        $this->testCountingMaxUsesCondition($user);

        $this->actingAs($this->$user)->postJson('/orders', [
            'email' => 'info@example.com',
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'billing_address' => $this->address,
            'shipping_place' => $this->address,
            'items' => $this->items,
        ])->assertCreated();

        $this->discount->refresh();
        $this->assertEquals(1, $this->discount->uses);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCountingMaxUsesPerUserCondition($user): void
    {
        $this->$user->givePermissionTo('orders.add');

        $this->discount->products()->attach($this->product->getKey());
        $this->discount->conditionGroups()->attach($this->conditionGroup);
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
        ])->assertCreated();

        $this->discount->refresh();
        $this->assertEquals(1, $this->discount->uses);

        $this->actingAs($this->$user)->postJson('/orders', [
            'email' => 'info@example.com',
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'billing_address' => $this->address,
            'shipping_place' => $this->address,
            'items' => $this->items,
        ])->assertCreated();

        $this->discount->refresh();
        $this->assertEquals(1, $this->discount->uses);

        $otherUser = $this->user = User::factory()->create();
        $otherUser->givePermissionTo('orders.add');
        Auth::setUser($otherUser);

        $this->actingAs($otherUser)->postJson('/orders', [
            'email' => 'info@example.com',
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'billing_address' => $this->address,
            'shipping_place' => $this->address,
            'items' => $this->items,
        ])->assertCreated();

        $this->discount->refresh();
        $this->assertEquals(2, $this->discount->uses);
    }
}
