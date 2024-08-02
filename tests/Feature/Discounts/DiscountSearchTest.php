<?php

namespace Tests\Feature\Discounts;

use App\Enums\ConditionType;
use App\Models\ConditionGroup;
use App\Models\Discount;
use App\Models\DiscountCondition;
use App\Models\Role;
use Domain\Organization\Models\Organization;
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
            'code' => $kind === 'coupons' ? 'TEST1' : null,
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

        /** @var Discount $discount2 */
        $discount2 = Discount::factory()->create([
            'code' => $kind === 'coupons' ? 'TEST2' : null,
        ]);

        /** @var ConditionGroup $conditionGroup2 */
        $conditionGroup2 = $discount2->conditionGroups()->create();

        DiscountCondition::query()->create([
            'condition_group_id' => $conditionGroup2->getKey(),
            'type' => ConditionType::USER_IN_ROLE,
            'value' => [
                'is_allow_list' => false,
                'roles' => [],
            ],
        ]);

        /** @var Discount $discount2 */
        $discount3 = Discount::factory()->create([
            'code' => $kind === 'coupons' ? 'TEST3' : null,
        ]);

        $this
            ->actingAs($this->user)
            ->json('GET', $kind, ['for_role' => $role->getKey()])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $discount->getKey()])
            ->assertJsonFragment(['id' => $discount2->getKey()])
            ->assertJsonMissing(['id' => $discount3->getKey()]);
    }

    /**
     * @dataProvider couponOrSaleProvider
     */
    public function testIndexSearchByOrganization($kind): void
    {
        $this->user->givePermissionTo("{$kind}.show");

        $organization = Organization::factory()->create();

        /** @var Discount $discount */
        $discount = Discount::factory()->create([
            'code' => $kind === 'coupons' ? 'TEST1' : null,
        ]);

        /** @var ConditionGroup $conditionGroup */
        $conditionGroup = $discount->conditionGroups()->create();

        DiscountCondition::query()->create([
            'condition_group_id' => $conditionGroup->getKey(),
            'type' => ConditionType::USER_IN_ORGANIZATION,
            'value' => [
                'is_allow_list' => true,
                'organizations' => [$organization->getKey()],
            ],
        ]);

        /** @var Discount $discount2 */
        $discount2 = Discount::factory()->create([
            'code' => $kind === 'coupons' ? 'TEST2' : null,
        ]);

        /** @var ConditionGroup $conditionGroup2 */
        $conditionGroup2 = $discount2->conditionGroups()->create();

        DiscountCondition::query()->create([
            'condition_group_id' => $conditionGroup2->getKey(),
            'type' => ConditionType::USER_IN_ORGANIZATION,
            'value' => [
                'is_allow_list' => false,
                'organizations' => [],
            ],
        ]);

        /** @var Discount $discount3 */
        $discount3 = Discount::factory()->create([
            'code' => $kind === 'coupons' ? 'TEST3' : null,
        ]);

        /** @var Discount $discount4 */
        $discount4 = Discount::factory()->create([

            'code' => $kind === 'coupons' ? 'TEST4' : null,
        ]);

        /** @var ConditionGroup $conditionGroup4 */
        $conditionGroup4 = $discount4->conditionGroups()->create();

        DiscountCondition::query()->create([
            'condition_group_id' => $conditionGroup4->getKey(),
            'type' => ConditionType::USER_IN_ORGANIZATION,
            'value' => [
                'is_allow_list' => true,
                'organizations' => [$organization->getKey()],
            ],
        ]);

        DiscountCondition::query()->create([
            'condition_group_id' => $conditionGroup4->getKey(),
            'type' => ConditionType::USER_IN_ORGANIZATION,
            'value' => [
                'is_allow_list' => false,
                'organizations' => [$organization->getKey()],
            ],
        ]);

        $this
            ->actingAs($this->user)
            ->json('GET', $kind, ['for_organization' => $organization->getKey()])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $discount->getKey()])
            ->assertJsonFragment(['id' => $discount2->getKey()])
            ->assertJsonMissing(['id' => $discount3->getKey()])
            ->assertJsonMissing(['id' => $discount4->getKey()]);
    }
}
