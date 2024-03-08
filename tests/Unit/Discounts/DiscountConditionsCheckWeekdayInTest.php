<?php

namespace Tests\Unit\Discounts;

use App\Enums\ConditionType;
use App\Models\DiscountCondition;
use Brick\Money\Money;
use Illuminate\Support\Carbon;

class DiscountConditionsCheckWeekdayInTest extends DiscountConditionsCheckCase
{
    public function testCheckConditionWeekdayInPass(): void
    {
        Carbon::setTestNow('2022-03-04T12:00:00'); // piątek

        /** @var DiscountCondition $discountCondition */
        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::WEEKDAY_IN,
            'value' => [
                'weekday' => [false, false, false, false, false, true, false],
            ],
        ]);

        $this->assertTrue(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value))
        );
    }

    public function testCheckConditionWeekdayInFail(): void
    {
        Carbon::setTestNow('2022-03-04T12:00:00'); // piątek

        /** @var DiscountCondition $discountCondition */
        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::WEEKDAY_IN,
            'value' => [
                'weekday' => [true, true, true, true, true, false, true],
            ],
        ]);

        $this->assertFalse(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value))
        );
    }
}
