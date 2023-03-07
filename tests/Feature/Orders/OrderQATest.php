<?php

namespace Tests\Feature\Orders;

use App\Enums\ConditionType;
use App\Enums\DiscountTargetType;
use App\Enums\DiscountType;
use App\Models\ConditionGroup;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingMethod;
use Tests\TestCase;

/**
 * These are cases picked up by manual testers.
 */
class OrderQATest extends TestCase
{
    /**
     * HES-1962
     */
    public function testSalesAndCode(): void
    {
        $this->user->givePermissionTo('orders.add');

        /** @var Product $product */
        $product = Product::factory()->create([
            'price' => 100,
            'public' => true,
        ]);

        $coupon = Discount::factory()->create([
            'active' => true,
            'code' => 'minus10',
            'value' => 10,
            'type' => DiscountType::PERCENTAGE,
            'target_type' => DiscountTargetType::ORDER_VALUE,
        ]);

        $saleTotalValue = Discount::factory()->create([
            'active' => true,
            'code' => null,
            'value' => 10,
            'type' => DiscountType::PERCENTAGE,
            'target_type' => DiscountTargetType::ORDER_VALUE,
        ]);

        /** @var Discount $saleTotalValueWithCondition */
        $saleTotalValueWithCondition = Discount::factory()->create([
            'active' => true,
            'code' => null,
            'value' => 5,
            'type' => DiscountType::AMOUNT,
            'target_type' => DiscountTargetType::ORDER_VALUE,
        ]);
        /** @var ConditionGroup $conditionGroup */
        $conditionGroup = $saleTotalValueWithCondition->conditionGroups()->create();
        $conditionGroup->conditions()->create([
            'type' => ConditionType::ORDER_VALUE,
            'value' => [
                'min_value' => 0,
                'max_value' => 2222,
                'is_in_range' => true,
                'include_taxes' => true,
            ],
        ]);

        /** @var Discount $saleTargetProduct */
        $saleTargetProduct = Discount::factory()->create([
            'active' => true,
            'code' => null,
            'value' => 5,
            'type' => DiscountType::AMOUNT,
            'target_type' => DiscountTargetType::PRODUCTS,
        ]);
        $saleTargetProduct->products()->attach($product->getKey());
        /** @var ConditionGroup $conditionGroup */
        $conditionGroup = $saleTargetProduct->conditionGroups()->create();
        $conditionGroup->conditions()->create([
            'type' => ConditionType::ORDER_VALUE,
            'value' => [
                'min_value' => 1,
                'max_value' => 555,
                'is_in_range' => true,
                'include_taxes' => true,
            ],
        ]);

        $this
            ->actingAs($this->user)
            ->json('POST', '/orders', [
                'email' => 'test@example.com',
                'shipping_method_id' => ShippingMethod::factory()->create()->getKey(),
                'items' => [[
                    'product_id' => $product->getKey(),
                    'quantity' => 1,
                ],
                ],
                'coupons' => [
                    $coupon->code,
                ],
                'shipping_place' => [
                    'name' => 'test test',
                    'address' => 'Gdańska 89/1',
                    'vat' => '9571099580',
                    'zip' => '80-200',
                    'city' => 'Bydgoszcz',
                    'country' => 'PL',
                    'phone' => '+48543234123',
                ],
                'billing_address' => [
                    'name' => 'Jan  kod+promka+naProdukt3',
                    'address' => 'Gdańska 89/1',
                    'vat' => '9571099580',
                    'zip' => '80-200',
                    'city' => 'Bydgoszcz',
                    'country' => 'PL',
                    'phone' => '+48543234123',
                ],
            ])
            ->assertCreated();

//        $this->assertDatabaseHas('orders', [
//            'shipping_price_initial' => 0,
//            'shipping_price' => 0,
//            'cart_total_initial' => 100,
//            'summary' => 76.95, // wrong number (without discount with target product)
//        ]);

        /** @var Order $order */
        $order = Order::query()->first();

        $this->assertCount(4, $order->discounts);

//        dd($order->discounts);
    }
}
