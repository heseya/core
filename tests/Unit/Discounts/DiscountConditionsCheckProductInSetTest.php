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
use Domain\ProductSet\ProductSet;
use Domain\SalesChannel\Models\SalesChannel;
use Domain\ShippingMethod\Models\ShippingMethod;
use Heseya\Dto\DtoException;
use Illuminate\Support\Facades\App;
use Tests\Utils\FakeDto;

class DiscountConditionsCheckProductInSetTest extends DiscountConditionsCheckCase
{
    private ProductService $productService;
    private ShippingMethod $shippingMethod;
    private CartDto $cart;
    private Product $product;

    public function setUp(): void
    {
        parent::setUp();

        $this->productService = App::make(ProductService::class);
        $this->shippingMethod = ShippingMethod::factory()->create();

        $this->product = $this->productService->create(FakeDto::productCreateDto());

        $this->cart = CartDto::fromArray([
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'items' => [
                [
                    'cartitem_id' => 0,
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
            ],
            'coupons' => [],
            'shipping_method_id' => $this->shippingMethod->getKey(),
        ]);
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
        $set1 = ProductSet::factory()->create();
        $set2 = ProductSet::factory()->create();

        $this->product->sets()->sync([$set1->getKey()]);

        /** @var DiscountCondition $discountCondition */
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
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value), $this->cart)
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
        $set1 = ProductSet::factory()->create();
        $childrenSet = ProductSet::factory()->create([
            'parent_id' => $set1->getKey(),
        ]);
        $subChildrenSet = ProductSet::factory()->create([
            'parent_id' => $childrenSet->getKey(),
        ]);

        $this->product->sets()->sync([$subChildrenSet->getKey()]);

        /** @var DiscountCondition $discountCondition */
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
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value), $this->cart)
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
        $set1 = ProductSet::factory()->create();
        $set2 = ProductSet::factory()->create();

        $this->product->sets()->sync([$set1->getKey()]);

        /** @var DiscountCondition $discountCondition */
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
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value), $this->cart)
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
        $set1 = ProductSet::factory()->create();
        $childrenSet = ProductSet::factory()->create([
            'parent_id' => $set1->getKey(),
        ]);
        $subChildrenSet = ProductSet::factory()->create([
            'parent_id' => $childrenSet->getKey(),
        ]);
        $set2 = ProductSet::factory()->create();

        $this->product->sets()->sync([$subChildrenSet->getKey()]);

        /** @var DiscountCondition $discountCondition */
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
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value), $this->cart)
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
        $set1 = ProductSet::factory()->create();
        $set2 = ProductSet::factory()->create();

        $this->product->sets()->sync([$set1->getKey()]);

        /** @var DiscountCondition $discountCondition */
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
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value), $this->cart)
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
        $set1 = ProductSet::factory()->create();
        $childrenSet = ProductSet::factory()->create([
            'parent_id' => $set1->getKey(),
        ]);
        $subChildrenSet = ProductSet::factory()->create([
            'parent_id' => $childrenSet->getKey(),
        ]);
        $set2 = ProductSet::factory()->create();

        $this->product->sets()->sync([$subChildrenSet->getKey()]);

        /** @var DiscountCondition $discountCondition */
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
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value), $this->cart)
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
        ProductSet::factory()->create();
        $set2 = ProductSet::factory()->create();

        $this->product->sets()->sync([$set2->getKey()]);

        /** @var DiscountCondition $discountCondition */
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
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value), $this->cart)
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
        $set1 = ProductSet::factory()->create();
        $childrenSet = ProductSet::factory()->create([
            'parent_id' => $set1->getKey(),
        ]);
        $subChildrenSet = ProductSet::factory()->create([
            'parent_id' => $childrenSet->getKey(),
        ]);

        $this->product->sets()->sync([$subChildrenSet->getKey()]);

        /** @var DiscountCondition $discountCondition */
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
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value), $this->cart)
        );
    }
}
