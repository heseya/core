<?php

namespace Tests\Feature;

use App\Enums\ConditionType;
use App\Models\ConditionGroup;
use App\Models\Discount;
use App\Models\DiscountCondition;
use App\Models\Role;
use Tests\TestCase;

class DiscountSearchTest extends TestCase
{
    public function couponOrSaleProvider(): array
    {
        return [
            'coupons' => ['coupons'],
            'sales' => ['sales'],
        ];
    }

    /**
     * @dataProvider couponOrSaleProvider
     */
    public function testIndexSearchByRole($kind): void
    {
        $this->user->givePermissionTo("{$kind}.show");

        $role = Role::query()->create([
            'name' => 'test',
        ]);

        /** @var Discount $discount */
        $discount = Discount::factory()->create([
            'code' => $kind === 'coupons' ? 'TEST' : null,
        ]);

        /** @var ConditionGroup $conditionGroup */
        $conditionGroup = $discount->conditionGroups()->create();

        DiscountCondition::query()->create([
            'condition_group_id' => $conditionGroup->getKey(),
            'type' => ConditionType::USER_IN_ROLE,
            'value' => [
                'is_allow_list' => true,
                'roles' => [$role->getKey()],
            ],
        ]);

        $this
            ->actingAs($this->user)
            ->json('GET', $kind, ['for_role' => $role->getKey()])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $discount->getKey()]);
    }
}
