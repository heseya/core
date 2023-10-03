<?php

namespace Tests\Feature\Discounts;

use App\Enums\ConditionType;
use App\Models\ConditionGroup;
use App\Models\Discount;
use App\Models\DiscountCondition;
use App\Models\Role;
use Tests\TestCase;

class DiscountSearchTest extends TestCase
{
    public static function couponOrSaleProvider(): array
    {
        return [
            'coupons' => ['coupons'],
            'sales' => ['sales'],
        ];
    }

    /**
     * @dataProvider couponOrSaleProvider
     */
    public function testIndexSearch($kind): void
    {
        $this->user->givePermissionTo("{$kind}.show");

        $code = $kind === 'coupons' ? [] : ['code' => null];
        $discount1 = Discount::factory()->create($code + ['name' => 'Discount 1', 'percentage' => '30']);
        $discount2 = Discount::factory()->create($code + ['name' => 'Discount 2', 'percentage' => '15']);

        $this
            ->actingAs($this->user)
            ->json('GET', $kind, ['search' => '15'])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $discount2->getKey()])
            ->assertJsonMissing(['id' => $discount1->getKey()]);
    }

    /**
     * @dataProvider couponOrSaleProvider
     */
    public function testIndexSearchByName($kind): void
    {
        $this->user->givePermissionTo("{$kind}.show");

        $code = $kind === 'coupons' ? [] : ['code' => null];
        $discount1 = Discount::factory()->create($code + ['name' => 'Discount 1', 'percentage' => '30']);
        $discount2 = Discount::factory()->create($code + ['name' => 'Discount 2', 'percentage' => '15']);

        $this
            ->actingAs($this->user)
            ->json('GET', $kind, ['search' => 'Discount 2'])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $discount2->getKey()])
            ->assertJsonMissing(['id' => $discount1->getKey()]);
    }

    /**
     * @dataProvider couponOrSaleProvider
     */
    public function testIndexSearchByIds($kind): void
    {
        $this->user->givePermissionTo("{$kind}.show");

        $code = $kind === 'coupons' ? [] : ['code' => null];
        $discount1 = Discount::factory()->create($code + ['name' => 'Discount 1', 'percentage' => '30']);
        $discount2 = Discount::factory()->create($code + ['name' => 'Discount 2', 'percentage' => '15']);

        $this
            ->actingAs($this->user)
            ->json(
                'GET',
                $kind,
                [
                    'ids' => [
                        $discount2->getKey(),
                    ],
                ],
            )
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $discount2->getKey()])
            ->assertJsonMissing(['id' => $discount1->getKey()]);
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
