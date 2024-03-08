<?php

namespace Tests\Unit\Discounts;

use App\Enums\ConditionType;
use App\Models\DiscountCondition;
use App\Models\Role;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Support\Facades\Auth;

class DiscountConditionsCheckUserInRoleTest extends DiscountConditionsCheckCase
{
    public function testCheckConditionUserInRolePass(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $role = Role::create(['name' => 'User role']);
        $user->assignRole($role);

        Auth::setUser($user);

        /** @var DiscountCondition $discountCondition */
        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::USER_IN_ROLE,
            'value' => [
                'roles' => [$role->getKey()],
                'is_allow_list' => true,
            ],
        ]);

        $this->assertTrue(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value))
        );
    }

    public function testCheckConditionUserInRoleAllowListFalsePass(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $userRole = Role::create(['name' => 'User role']);
        $role = Role::create(['name' => 'Discount Role']);

        $user->assignRole($userRole);
        Auth::setUser($user);

        /** @var DiscountCondition $discountCondition */
        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::USER_IN_ROLE,
            'value' => [
                'roles' => [$role->getKey()],
                'is_allow_list' => false,
            ],
        ]);

        $this->assertTrue(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value))
        );
    }

    public function testCheckConditionUserInRoleFail(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $role = Role::create(['name' => 'User role']);

        Auth::setUser($user);

        /** @var DiscountCondition $discountCondition */
        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::USER_IN_ROLE,
            'value' => [
                'roles' => [$role->getKey()],
                'is_allow_list' => true,
            ],
        ]);

        $this->assertFalse(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value))
        );
    }

    public function testCheckConditionUserInRoleAllowListFalseFail(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $role = Role::create(['name' => 'User role']);
        $user->assignRole($role);

        Auth::setUser($user);

        /** @var DiscountCondition $discountCondition */
        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::USER_IN_ROLE,
            'value' => [
                'roles' => [$role->getKey()],
                'is_allow_list' => false,
            ],
        ]);

        $this->assertFalse(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value))
        );
    }
}
