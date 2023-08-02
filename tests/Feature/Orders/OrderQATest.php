<?php

namespace Tests\Feature\Orders;

use App\Dtos\PriceDto;
use App\Enums\ConditionType;
use App\Enums\DiscountTargetType;
use App\Enums\DiscountType;
use App\Models\ConditionGroup;
use App\Models\Discount;
use App\Models\Order;
use App\Models\PriceRange;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Services\Contracts\ProductServiceContract;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domains\Currency\Currency;
use Heseya\Dto\DtoException;
use Illuminate\Support\Facades\App;
use Tests\TestCase;
use Tests\Utils\FakeDto;

/**
 * These are cases picked up by manual testers.
 */
class OrderQATest extends TestCase
{
    private const ADDRESS = [
        'name' => 'test test',
        'address' => 'Gdańska 89/1',
        'vat' => '9571099580',
        'zip' => '80-200',
        'city' => 'Bydgoszcz',
        'country' => 'PL',
        'phone' => '+48543234123',
    ];

    private Product $product;
    private ShippingMethod $shippingMethod;

    /**
     * @throws RoundingNecessaryException
     * @throws DtoException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     */
    public function setUp(): void
    {
        parent::setUp();

        $currency = Currency::DEFAULT->value;

        /** @var ProductServiceContract $productService */
        $productService = App::make(ProductServiceContract::class);
        $this->product = $productService->create(FakeDto::productCreateDto([
            'prices_base' => [new PriceDto(Money::of(100, $currency))],
            'public' => true,
        ]));

        $this->shippingMethod = ShippingMethod::factory()->create();
        $freeRange = PriceRange::query()->create([
            'start' => Money::zero($currency),
            'value' => Money::zero($currency),
        ]);
        $this->shippingMethod->priceRanges()->save($freeRange);
    }

    public function testSalesAndCode(): void
    {
        $this->user->givePermissionTo('orders.add');

        $coupon = Discount::factory()->create([
            'active' => true,
            'code' => 'minus10',
            'value' => 10,
            'type' => DiscountType::PERCENTAGE,
            'target_type' => DiscountTargetType::ORDER_VALUE,
        ]);

        Discount::factory()->create([
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
        $saleTargetProduct = $this->product->discounts()->create([
            'name' => 'Sale Target Product',
            'priority' => 0,
            'active' => true,
            'code' => null,
            'value' => 5,
            'type' => DiscountType::AMOUNT,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ]);
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
                'shipping_method_id' => $this->shippingMethod->getKey(),
                'items' => [[
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                ]],
                'coupons' => [
                    $coupon->code,
                ],
                'shipping_place' => self::ADDRESS,
                'billing_address' => self::ADDRESS,
            ])
            ->assertCreated();

        /** @var Order $order */
        $order = Order::query()->first();

        $this->assertEquals(100, $order->products[0]->price_initial);
        $this->assertEquals(95, $order->products[0]->price);
        $this->assertCount(1, $order->products);
        $this->assertCount(1, $order->products[0]->discounts); // 1 discount on product
        $this->assertCount(3, $order->discounts); // 3 discounts on order
        $this->assertEquals(100, $order->cart_total_initial);
        $this->assertEquals(72.9, $order->summary);
    }

    public function testTargetProductSale(): void
    {
        $this->user->givePermissionTo('orders.add');

        /** @var Discount $saleTargetProduct */
        $saleTargetProduct = $this->product->discounts()->create([
            'name' => 'Sale Target Product',
            'priority' => 0,
            'active' => true,
            'code' => null,
            'value' => 5,
            'type' => DiscountType::AMOUNT,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ]);

        $this
            ->actingAs($this->user)
            ->json('POST', '/orders', [
                'email' => 'test@example.com',
                'shipping_method_id' => $this->shippingMethod->getKey(),
                'items' => [[
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                ]],
                'shipping_place' => self::ADDRESS,
                'billing_address' => self::ADDRESS,
            ])
            ->assertCreated();

        /** @var Order $order */
        $order = Order::query()->first();

        $this->assertEquals(100, $order->cart_total_initial);
        $this->assertEquals(95, $order->summary);
        $this->assertCount(1, $order->products);
        $this->assertEquals(100, $order->products[0]->price_initial);
        $this->assertEquals(95, $order->products[0]->price);
        $this->assertCount(1, $order->products[0]->discounts);
        $this->assertEquals($saleTargetProduct->getKey(), $order->products[0]->discounts[0]->getKey());
    }
}
