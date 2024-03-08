<?php

namespace Tests\Unit\Discounts;

use App\Dtos\CartDto;
use App\Enums\ConditionType;
use App\Models\DiscountCondition;
use App\Services\ProductService;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domain\SalesChannel\Models\SalesChannel;
use Domain\ShippingMethod\Models\ShippingMethod;
use Heseya\Dto\DtoException;
use Illuminate\Support\Facades\App;
use Tests\Utils\FakeDto;

class DiscountConditionsCheckCartLengthTest extends DiscountConditionsCheckCase
{
    /**
     * @return array<string, array<int, array<int, mixed>>>
     */
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

    /**
     * @return array<string, array<int, array<int, mixed>>>
     */
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

    private ProductService $productService;
    private ShippingMethod $shippingMethod;

    public function setUp(): void
    {
        parent::setUp();

        $this->productService = App::make(ProductService::class);
        $this->shippingMethod = ShippingMethod::factory()->create();
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

        /** @var DiscountCondition $discountCondition */
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

        /** @var DiscountCondition $discountCondition */
        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::CART_LENGTH,
            'value' => $value,
        ]);

        $this->assertTrue($cart->getCartLength() === $quantity1 + $quantity2);
        $this->assertFalse(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value), $cart)
        );
    }
}
