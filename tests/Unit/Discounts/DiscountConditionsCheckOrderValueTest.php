<?php

namespace Tests\Unit\Discounts;

use App\Enums\ConditionType;
use App\Models\DiscountCondition;
use Brick\Money\Money;
use Domain\Price\Enums\DiscountConditionPriceType;

class DiscountConditionsCheckOrderValueTest extends DiscountConditionsCheckCase
{
    public function testCheckConditionOrderValuePass(): void
    {
        /** @var DiscountCondition $discountCondition */
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
        /** @var DiscountCondition $discountCondition */
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
        /** @var DiscountCondition $discountCondition */
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
        /** @var DiscountCondition $discountCondition */
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
}
