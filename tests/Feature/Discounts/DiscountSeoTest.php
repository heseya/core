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
                    'translations' => [
                        $this->lang => [
                            'title' => 'Great Sale!',
                            'description' => 'Really Great',
                        ],
                    ],
                ],
            ])
            ->assertCreated()
            ->assertJsonFragment([
                'title' => 'Great Sale!',
                'description' => 'Really Great',
            ]);

        $this->assertDatabaseHas('seo_metadata', [
            'model_id' => $response->json('data.id'),
            'model_type' => Discount::class,
            "title->{$this->lang}" => 'Great Sale!',
            "description->{$this->lang}" => 'Really Great',
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
                    'translations' => [
                        $this->lang => [
                            'title' => 'Sale',
                            'description' => 'Interesting business proposition',
                        ],
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonFragment([
                'title' => 'Sale',
                'description' => 'Interesting business proposition',
            ]);

        $this->assertDatabaseHas('seo_metadata', [
            'model_id' => $sale->getKey(),
            'model_type' => Discount::class,
            "title->{$this->lang}" => 'Sale',
            "description->{$this->lang}" => 'Interesting business proposition',
        ]);
    }
}
