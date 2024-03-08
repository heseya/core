<?php

namespace Tests\Unit\Discounts;

use App\Enums\ConditionType;
use App\Models\DiscountCondition;
use Brick\Money\Money;

class DiscountConditionsCheckMaxUsesTest extends DiscountConditionsCheckCase
{
    public function testCheckConditionMaxUsesPass(): void
    {
        /** @var DiscountCondition $discountCondition */
        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::MAX_USES,
            'value' => [
                'max_uses' => 100,
            ],
        ]);

        $this->assertTrue(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value))
        );
    }

    public function testCheckConditionMaxUsesFail(): void
    {
        /** @var DiscountCondition $discountCondition */
        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::MAX_USES,
            'value' => [
                'max_uses' => 0,
            ],
        ]);

        $this->assertFalse(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value))
        );
    }
}
