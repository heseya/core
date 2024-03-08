<?php

namespace Tests\Unit\Discounts;

use App\Dtos\CartDto;
use App\Enums\ConditionType;
use App\Models\DiscountCondition;
use App\Models\Product;
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

class DiscountConditionsCheckProductInTest extends DiscountConditionsCheckCase
{
    private CartDto $cart;
    private Product $product1;
    private Product $product2;

    public function setUp(): void
    {
        parent::setUp();

        $productService = App::make(ProductService::class);
        $shippingMethod = ShippingMethod::factory()->create();

        $this->product1 = $productService->create(FakeDto::productCreateDto());
        $this->product2 = $productService->create(FakeDto::productCreateDto());

        $this->cart = CartDto::fromArray([
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'items' => [
                [
                    'cartitem_id' => 0,
                    'product_id' => $this->product1->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
            ],
            'coupons' => [],
            'shipping_method_id' => $shippingMethod->getKey(),
        ]);
    }

    /**
     * @throws DtoException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     */
    public function testCheckConditionProductInPass(): void
    {
        /** @var DiscountCondition $discountCondition */
        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::PRODUCT_IN,
            'value' => [
                'products' => [
                    $this->product1->getKey(),
                    $this->product2->getKey(),
                ],
                'is_allow_list' => true,
            ],
        ]);

        $this->assertTrue(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value), $this->cart)
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
        /** @var DiscountCondition $discountCondition */
        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::PRODUCT_IN,
            'value' => [
                'products' => [
                    $this->product2->getKey(),
                ],
                'is_allow_list' => true,
            ],
        ]);

        $this->assertFalse(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value), $this->cart)
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
        /** @var DiscountCondition $discountCondition */
        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::PRODUCT_IN,
            'value' => [
                'products' => [
                    $this->product2->getKey(),
                ],
                'is_allow_list' => false,
            ],
        ]);

        $this->assertTrue(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value), $this->cart)
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
        /** @var DiscountCondition $discountCondition */
        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::PRODUCT_IN,
            'value' => [
                'products' => [
                    $this->product1->getKey(),
                ],
                'is_allow_list' => false,
            ],
        ]);

        $this->assertFalse(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value), $this->cart)
        );
    }
}
