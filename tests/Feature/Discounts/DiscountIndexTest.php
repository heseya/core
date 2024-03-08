<?php

namespace Tests\Feature\Discounts;

use App\Enums\DiscountTargetType;
use App\Models\Discount;
use Tests\TestCase;

class DiscountIndexTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // coupons
        Discount::factory()->count(10)->create();
        // sales
        Discount::factory([
            'code' => null,
            'target_type' => DiscountTargetType::ORDER_VALUE,
        ])->count(10)->create();
    }

    /**
     * @dataProvider couponOrSaleProvider
     */
    public function testIndexUnauthorized($discountKind): void
    {
        $response = $this->getJson("/{$discountKind}");
        $response->assertForbidden();
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testIndex($user, $discountKind): void
    {
        $this->{$user}->givePermissionTo("{$discountKind}.show");

        $response = $this
            ->actingAs($this->{$user})
            ->getJson("/{$discountKind}");

        $response->assertOk()
            ->assertJsonCount(10, 'data');

        $this->assertQueryCountLessThan(24);
    }

    /**
     * @dataProvider authWithDiscountProvider
     */
    public function testIndexPerformance($user, $discountKind): void
    {
        $this->{$user}->givePermissionTo("{$discountKind}.show");

        $codes = $discountKind === 'coupons' ? [] : ['code' => null];
        Discount::factory($codes)->count(490)->create();

        $response = $this
            ->actingAs($this->{$user})
            ->getJson("/{$discountKind}?limit=500");

        $response->assertOk()
            ->assertJsonCount(500, 'data');

        // It's now 512 ugh
        $this->assertQueryCountLessThan(15);
    }
}
