<?php

namespace Tests\Feature\Discounts;

use App\Enums\ConditionType;
use App\Models\ConditionGroup;
use App\Models\Discount;

class DiscountViewTest extends DiscountTestCase
{
    public function testShowUnauthorized(): void
    {
        $discount = Discount::factory()->create();

        $response = $this->getJson('/coupons/' . $discount->code);
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testShow($user): void
    {
        $this->{$user}->givePermissionTo('coupons.show_details');
        $discount = Discount::factory()->create();

        $response = $this->actingAs($this->{$user})->getJson('/coupons/' . $discount->code);
        $response
            ->assertOk()
            ->assertJsonStructure($this->expectedStructure)
            ->assertJsonFragment([
                'id' => $discount->getKey(),
                'active' => true,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowInactiveByCode($user): void
    {
        $this->{$user}->givePermissionTo('coupons.show_details');
        $discount = Discount::factory()->create([
            'active' => false,
        ]);

        $this
            ->actingAs($this->{$user})
            ->getJson('/coupons/' . $discount->code)
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowWithConditions(string $user): void
    {
        $this->{$user}->givePermissionTo('coupons.show_details');
        $discount = Discount::factory()->create();

        $conditionGroup = ConditionGroup::create();

        $condition = $conditionGroup->conditions()->create([
            'type' => ConditionType::USER_IN_ROLE,
            'value' => [
                'roles' => [
                    $this->role->getKey(),
                ],
                'is_allow_list' => true,
            ],
        ]);
        $condition->roles()->attach($this->role);

        $condition2 = $conditionGroup->conditions()->create([
            'type' => ConditionType::USER_IN,
            'value' => [
                'users' => [
                    $this->conditionUser->getKey(),
                ],
                'is_allow_list' => true,
            ],
        ]);

        $condition2->users()->attach($this->conditionUser);

        $condition3 = $conditionGroup->conditions()->create([
            'type' => ConditionType::PRODUCT_IN_SET,
            'value' => [
                'product_sets' => [
                    $this->conditionProductSet->getKey(),
                ],
                'is_allow_list' => true,
            ],
        ]);

        $condition3->productSets()->attach($this->conditionProductSet);

        $condition4 = $conditionGroup->conditions()->create([
            'type' => ConditionType::PRODUCT_IN,
            'value' => [
                'products' => [
                    $this->conditionProduct->getKey(),
                ],
                'is_allow_list' => true,
            ],
        ]);

        $condition4->products()->attach($this->conditionProduct);

        $discount->conditionGroups()->attach($conditionGroup);

        $response = $this->actingAs($this->{$user})->getJson('/coupons/' . $discount->code);
        $response
            ->assertOk()
            ->assertJsonStructure($this->expectedStructure)
            ->assertJsonFragment([
                'id' => $discount->getKey(),
                'name' => $discount->name,
                'description' => $discount->description,
                'percentage' => $discount->percentage !== null ? number_format($discount->percentage, 4) : null,
                'priority' => $discount->priority,
                'uses' => $discount->uses,
            ])
            ->assertJsonFragment([
                'type' => ConditionType::USER_IN_ROLE,
                'is_allow_list' => true,
            ])
            ->assertJsonFragment([
                'type' => ConditionType::USER_IN,
                'is_allow_list' => true,
            ])
            ->assertJsonFragment([
                'type' => ConditionType::PRODUCT_IN_SET,
                'is_allow_list' => true,
            ])
            ->assertJsonFragment([
                'type' => ConditionType::PRODUCT_IN,
                'is_allow_list' => true,
            ])
            ->assertJsonFragment([
                'id' => $this->role->getKey(),
                'name' => $this->role->name,
                'description' => $this->role->description,
            ])
            ->assertJsonFragment([
                'id' => $this->conditionUser->getKey(),
                'name' => $this->conditionUser->name,
                'email' => $this->conditionUser->email,
            ])
            ->assertJsonFragment([
                'id' => $this->conditionProductSet->getKey(),
                'name' => $this->conditionProductSet->name,
                'slug' => $this->conditionProductSet->slug,
            ])
            ->assertJsonFragment([
                'id' => $this->conditionProduct->getKey(),
                'name' => $this->conditionProduct->name,
                'slug' => $this->conditionProduct->slug,
            ]);
    }

    /**
     * @dataProvider couponOrSaleProvider
     */
    public function testShowByIdUnauthorized(string $discountKind): void
    {
        $code = $discountKind === 'coupons' ? [] : ['code' => null];

        $discount = Discount::factory($code)->create();

        $response = $this->getJson("/{$discountKind}/id:" . $discount->getKey());
        $response->assertForbidden();
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testShowById(string $user, string $discountKind): void
    {
        $this->{$user}->givePermissionTo("{$discountKind}.show_details");

        $code = $discountKind === 'coupons' ? [] : ['code' => null];
        $discount = Discount::factory($code)->create();

        $this
            ->actingAs($this->{$user})
            ->getJson("/{$discountKind}/id:" . $discount->getKey())
            ->assertOk()
            ->assertJsonFragment([
                'id' => $discount->getKey(),
                'active' => true,
            ]);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testShowByIdInactive(string $user, string $discountKind): void
    {
        $this->{$user}->givePermissionTo("{$discountKind}.show_details");

        $code = $discountKind === 'coupons' ? [] : ['code' => null];
        $discount = Discount::factory($code)->create([
            'active' => false,
        ]);

        $this
            ->actingAs($this->{$user})
            ->getJson("/{$discountKind}/id:" . $discount->getKey())
            ->assertOk()
            ->assertJsonFragment([
                'id' => $discount->getKey(),
            ]);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testShowInvalidDiscount(string $user, string $discountKind): void
    {
        $this->{$user}->givePermissionTo("{$discountKind}.show_details");

        $code = $discountKind === 'sales' ? [] : ['code' => null];
        $discount = Discount::factory($code)->create();

        $this
            ->actingAs($this->{$user})
            ->json('GET', "/{$discountKind}/id:" . $discount->getKey())
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowWrongCode(string $user): void
    {
        $this->{$user}->givePermissionTo('coupons.show_details');

        /** @var Discount $discount */
        $discount = Discount::factory()->create();

        $this
            ->actingAs($this->{$user})
            ->getJson('/coupons/its_not_code')
            ->assertNotFound();

        $this
            ->actingAs($this->{$user})
            ->getJson('/coupons/' . $discount->code . '_' . $discount->code)
            ->assertNotFound();
    }
}
