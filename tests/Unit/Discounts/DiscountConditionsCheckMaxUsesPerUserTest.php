<?php

namespace Tests\Unit\Discounts;

use App\Enums\ConditionType;
use App\Models\DiscountCondition;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Support\Facades\Auth;

class DiscountConditionsCheckMaxUsesPerUserTest extends DiscountConditionsCheckCase
{
    public function testCheckConditionMaxUsesPerUserPass(): void
    {
        Auth::setUser(User::factory()->create());
        /** @var DiscountCondition $discountCondition */
        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::MAX_USES_PER_USER,
            'value' => [
                'max_uses' => 100,
            ],
        ]);

        $this->assertTrue(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value))
        );
    }

    public function testCheckConditionMaxUsesPerUserFail(): void
    {
        Auth::setUser(User::factory()->create());
        /** @var DiscountCondition $discountCondition */
        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::MAX_USES_PER_USER,
            'value' => [
                'max_uses' => 0,
            ],
        ]);

        $this->assertFalse(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value))
        );
    }

    public function testCheckConditionMaxUsesPerUserNoAuthUser(): void
    {
        /** @var DiscountCondition $discountCondition */
        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::MAX_USES_PER_USER,
            'value' => [
                'max_uses' => 100,
            ],
        ]);

        $this->assertFalse(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value))
        );
    }
}
