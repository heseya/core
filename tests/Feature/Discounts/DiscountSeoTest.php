<?php

namespace Tests\Feature\Discounts;

use App\Enums\DiscountTargetType;
use App\Enums\DiscountType;
use App\Models\Discount;
use Tests\TestCase;

class DiscountSeoTest extends TestCase
{
    /**
     * @dataProvider authProvider
     */
    public function testCreate(string $user): void
    {
        $this->{$user}->givePermissionTo('sales.add');
        $response = $this
            ->actingAs($this->{$user})
            ->json('POST', '/sales', [
                'name' => 'Sale',
                'type' => DiscountType::PERCENTAGE,
                'value' => 10,
                'priority' => 1,
                'target_type' => DiscountTargetType::ORDER_VALUE,
                'target_is_allow_list' => true,
                'seo' => [
                    'title' => 'Great Sale!',
                    'description' => 'Really Great',
                ],
            ])
            ->assertCreated();

        $this->assertDatabaseHas('seo_metadata', [
            'model_id' => $response->json('data.id'),
            'model_type' => Discount::class,
            'title' => 'Great Sale!',
            'description' => 'Really Great',
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdate(string $user): void
    {
        $sale = Discount::factory()->create();

        $this->{$user}->givePermissionTo('coupons.edit');
        $this
            ->actingAs($this->{$user})
            ->json('PATCH', "/coupons/id:{$sale->getKey()}", [
                'seo' => [
                    'title' => 'Sale',
                    'description' => 'Interesting business proposition',
                ],
            ])
            ->assertOk();

        $this->assertDatabaseHas('seo_metadata', [
            'model_id' => $sale->getKey(),
            'model_type' => Discount::class,
            'title' => 'Sale',
            'description' => 'Interesting business proposition',
        ]);
    }
}
