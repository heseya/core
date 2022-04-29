<?php

namespace Tests\Feature;

use App\Enums\ConditionType;
use App\Enums\DiscountTargetType;
use App\Enums\DiscountType;
use App\Models\ConditionGroup;
use App\Models\Discount;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\ShippingMethod;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Tests\Traits\CreateShippingMethod;

class DiscountOrderTest extends TestCase
{
    use CreateShippingMethod;

    protected Product $product;
    protected ShippingMethod $shippingMethod;

    protected array $items;
    protected array $address;

    public function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->product = Product::factory()->create([
            'public' => true,
            'price' => 100,
        ]);

        $this->shippingMethod = $this->createShippingMethod(10);

        $this->items = [[
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
    public function testOrderCreateDiscountOrderValuePercentage($user): void
    {
        $this->$user->givePermissionTo('orders.add');

        $discount = Discount::factory()->create([
            'type' => DiscountType::PERCENTAGE,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'value' => 15,
        ]);

        $response = $this->actingAs($this->$user)->postJson('/orders', [
            'email' => 'info@example.com',
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'delivery_address' => $this->address,
            'items' => $this->items,
            'coupons' => [
                $discount->code,
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonFragment(['summary' => 95]); // 100 - 100 * 15% + 10 (delivery)

        $orderId = $response->getData()->data->id;

        $this->assertDatabaseHas('order_discounts', [
            'model_id' => $orderId,
            'discount_id' => $discount->getKey(),
            'model_type' => Order::class,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testOrderCreateOrderValueAmount($user): void
    {
        $this->$user->givePermissionTo('orders.add');

        $discount = Discount::factory()->create([
            'type' => DiscountType::AMOUNT,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'value' => 50,
        ]);

        $response = $this->actingAs($this->$user)->postJson('/orders', [
            'email' => 'info@example.com',
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'delivery_address' => $this->address,
            'items' => $this->items,
            'coupons' => [
                $discount->code,
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonFragment(['summary' => 60]); // 100 - 50 + 10 (delivery)
    }

    /**
     * @dataProvider authProvider
     */
    public function testOrderCreateChangeDiscountOrderValue($user): void
    {
        $this->$user->givePermissionTo('orders.add');

        $discount = Discount::factory()->create([
            'type' => DiscountType::PERCENTAGE,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'value' => 10,
        ]);

        $response = $this->actingAs($this->$user)->postJson('/orders', [
            'email' => 'info@example.com',
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'delivery_address' => $this->address,
            'items' => $this->items,
            'coupons' => [
                $discount->code,
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonFragment(['summary' => 100]); // 100 - 100 * 10% + 10 (delivery)

        $orderId = $response->getData()->data->id;

        $discount->update([
            'type' => DiscountType::AMOUNT,
            'discount' => 100,
        ]);

        $order = Order::find($orderId);
        $this->assertEquals(100, $order->summary);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCantCreateOrderSaleConditionsFail($user): void
    {
        $this->$user->givePermissionTo('orders.add');

        $discount = Discount::factory()->create([
            'type' => DiscountType::PERCENTAGE,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'value' => 15,
            'code' => null,
        ]);

        $conditionGroup = ConditionGroup::create();

        $conditionGroup->conditions()->create([
            'type' => ConditionType::MAX_USES,
            'value' => [
                'max_uses' => 0,
            ],
        ]);

        $discount->conditionGroups()->attach($conditionGroup);

        $this->actingAs($this->$user)->postJson('/orders', [
            'email' => 'info@example.com',
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'delivery_address' => $this->address,
            'items' => $this->items,
            'sale_ids' => [
                $discount->getKey(),
            ],
        ])->assertStatus(422);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCantCreateOrderCouponConditionFail($user): void
    {
        $this->$user->givePermissionTo('orders.add');

        $discount = Discount::factory()->create([
            'type' => DiscountType::PERCENTAGE,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'value' => 15,
        ]);

        $conditionGroup = ConditionGroup::create();

        $conditionGroup->conditions()->create([
            'type' => ConditionType::MAX_USES,
            'value' => [
                'max_uses' => 0,
            ],
        ]);

        $discount->conditionGroups()->attach($conditionGroup);

        $this->actingAs($this->$user)->postJson('/orders', [
            'email' => 'info@example.com',
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'delivery_address' => $this->address,
            'items' => $this->items,
            'coupons' => [
                $discount->code,
            ],
        ])->assertStatus(422);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateOrderMultipleDiscounts($user): void
    {
        $this->$user->givePermissionTo('orders.add');

        $shippingMethod = $this->createShippingMethod(20);

        $product1 = Product::factory()->create([
            'public' => true,
            'price' => 100,
        ]);

        $product2 = Product::factory()->create([
            'public' => true,
            'price' => 200,
        ]);

        $product3 = Product::factory()->create([
            'public' => true,
            'price' => 50,
        ]);

        $sale1 = Discount::factory()->create([
            'type' => DiscountType::PERCENTAGE,
            'target_type' => DiscountTargetType::PRODUCTS,
            'value' => 10,
            'target_is_allow_list' => true,
            'code' => null,
        ]);

        $sale1->products()->sync([$product1->getKey(), $product2->getKey()]);

        $sale2 = Discount::factory()->create([
            'type' => DiscountType::PERCENTAGE,
            'target_type' => DiscountTargetType::SHIPPING_PRICE,
            'value' => 100,
            'target_is_allow_list' => true,
            'code' => null,
        ]);

        $sale2->shippingMethods()->attach($shippingMethod);

        $conditionGroup1 = ConditionGroup::create();

        $conditionGroup1->conditions()->create(
            [
                'type' => ConditionType::ORDER_VALUE,
                'value' => [
                    'min_value' => 200,
                    'is_in_range' => true,
                    'include_taxes' => true,
                ],
            ],
        );

        $sale2->conditionGroups()->attach($conditionGroup1);

        $sale3 = Discount::factory()->create([
            'type' => DiscountType::AMOUNT,
            'target_type' => DiscountTargetType::PRODUCTS,
            'value' => 10,
            'target_is_allow_list' => true,
            'code' => null,
        ]);

        $sale3->products()->sync([$product1->getKey()]);

        $coupon = Discount::factory()->create([
            'type' => DiscountType::AMOUNT,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
            'value' => 50,
        ]);

        $conditionGroup2 = ConditionGroup::create();

        $conditionGroup2->conditions()->create([
            'type' => ConditionType::MAX_USES_PER_USER,
            'value' => [
                'max_uses' => 1,
            ],
        ]);

        $coupon->conditionGroups()->attach($conditionGroup2);

        $response = $this->actingAs($this->$user)->postJson('/orders', [
            'email' => 'info@example.com',
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'delivery_address' => $this->address,
            'items' => [
                [
                    'product_id' => $product1->getKey(),
                    'quantity' => 2,
                ],
                [
                    'product_id' => $product2->getKey(),
                    'quantity' => 1,
                ],
                [
                    'product_id' => $product3->getKey(),
                    'quantity' => 5,
                ],
            ],
            'coupons' => [
                $coupon->code,
            ],
            'sales_ids' => [
                $sale1->getKey(),
                $sale2->getKey(),
                $sale3->getKey(),
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonFragment(['summary' => 542]);

        $orderId = $response->getData()->data->id;

        $this->assertDatabaseCount('order_discounts', 5);

        $this->assertDatabaseHas('order_discounts', [
            'model_id' => $orderId,
            'model_type' => Order::class,
            'discount_id' => $sale2->getKey(),
        ]);

        $this->assertDatabaseHas('order_products', [
            'order_id' => $orderId,
            'product_id' => $product1->getKey(),
            'price' => 81.0,
        ]);

        $this->assertDatabaseHas('order_discounts', [
            'model_id' => $orderId,
            'model_type' => Order::class,
            'discount_id' => $coupon->getKey(),
        ]);

        $this->assertDatabaseHas('order_products', [
            'order_id' => $orderId,
            'product_id' => $product2->getKey(),
            'price' => 180.0,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testOrderCreateDiscountCheapestProduct($user): void
    {
        $this->$user->givePermissionTo('orders.add');

        $sale = Discount::factory()->create([
            'type' => DiscountType::PERCENTAGE,
            'target_type' => DiscountTargetType::PRODUCTS,
            'value' => 10,
            'target_is_allow_list' => true,
            'code' => null,
        ]);

        $sale->products()->attach($this->product);

        $cheapestDiscount = Discount::factory()->create([
            'type' => DiscountType::PERCENTAGE,
            'target_type' => DiscountTargetType::CHEAPEST_PRODUCT,
            'value' => 5,
            'code' => null,
        ]);

        $response = $this->actingAs($this->$user)->postJson('/orders', [
            'email' => 'info@example.com',
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'delivery_address' => $this->address,
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 2,
                ],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonFragment(['summary' => 185.5]); // 90 (first product) + 85,5 (second product) + 10 (delivery)

        $order = Order::find($response->getData()->data->id);

        $products = $order->products;

        $cheapestProduct = $products->sortBy('price')->first();
        $product = $products->sortBy('price')->last();

        $this->assertDatabaseCount('order_products', 2); // one for each product

        $this->assertDatabaseHas('order_products', [
            'order_id' => $order->getKey(),
            'product_id' => $this->product->getKey(),
            'price' => 85.5,
        ]);

        $this->assertDatabaseHas('order_products', [
            'order_id' => $order->getKey(),
            'product_id' => $this->product->getKey(),
            'price' => 90,
        ]);

        $this->assertDatabaseCount('order_discounts', 3);

        $this->assertDatabaseHas('order_discounts', [
            'model_id' => $cheapestProduct->getKey(),
            'model_type' => OrderProduct::class,
            'discount_id' => $cheapestDiscount->getKey(),
            'applied_discount' => 4.5,
        ]);

        $this->assertDatabaseHas('order_discounts', [
            'model_id' => $product->getKey(),
            'model_type' => OrderProduct::class,
            'discount_id' => $sale->getKey(),
            'applied_discount' => 10,
        ]);

        $this->assertDatabaseHas('order_discounts', [
            'model_id' => $cheapestProduct->getKey(),
            'model_type' => OrderProduct::class,
            'discount_id' => $sale->getKey(),
            'applied_discount' => 10,
        ]);
    }
}
