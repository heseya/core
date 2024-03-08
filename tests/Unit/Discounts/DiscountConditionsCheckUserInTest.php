<?php

namespace Tests\Unit\Discounts;

use App\Enums\ConditionType;
use App\Models\DiscountCondition;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Support\Facades\Auth;

class DiscountConditionsCheckUserInTest extends DiscountConditionsCheckCase
{
    public function testCheckConditionUserInPass(): void
    {
        $user = User::factory()->create();

        Auth::setUser($user);

        /** @var DiscountCondition $discountCondition */
        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::USER_IN,
            'value' => [
                'users' => [$user->getKey()],
                'is_allow_list' => true,
            ],
        ]);

        $this->assertTrue(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value))
        );
    }

    public function testCheckConditionUserInAllowListFalsePass(): void
    {
        $user = User::factory()->create();

        Auth::setUser($user);

        /** @var DiscountCondition $discountCondition */
        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::USER_IN,
            'value' => [
                'users' => [],
                'is_allow_list' => false,
            ],
        ]);

        $this->assertTrue(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value))
        );
    }

    public function testCheckConditionUserInFail(): void
    {
        $user = User::factory()->create();

        Auth::setUser($user);

        /** @var DiscountCondition $discountCondition */
        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::USER_IN,
            'value' => [
                'users' => [],
                'is_allow_list' => true,
            ],
        ]);

        $this->assertFalse(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value))
        );
    }

    public function testCheckConditionUserInAllowListFalseFail(): void
    {
        $user = User::factory()->create();

        Auth::setUser($user);

        /** @var DiscountCondition $discountCondition */
        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::USER_IN,
            'value' => [
                'users' => [$user->getKey()],
                'is_allow_list' => false,
            ],
        ]);

        $this->assertFalse(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value))
        );
    }
}
