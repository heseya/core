<?php

namespace Tests\Feature;

use App\Models\PriceRange;
use App\Models\ShippingMethod;
use Tests\TestCase;

class ShippingMethodPriceRangesTest extends TestCase
{
    /**
     * @dataProvider authProvider
     */
    public function testIndexByPrice($user): void
    {
        $this->$user->givePermissionTo('shipping_methods.show');

        $shippingMethod = ShippingMethod::factory()->create([
            'public' => true,
            'block_list' => false,
        ]);

        $shippingMethod->priceRanges()->saveMany([
            $priceRange1 = PriceRange::make(['start' => 0]),
            $priceRange2 = PriceRange::make(['start' => 1000]),
            $priceRange3 = PriceRange::make(['start' => 1500]),
        ]);

        $priceRange1->prices()->create(['value' => 20]);
        $priceRange2->prices()->create(['value' => 10]);
        $priceRange3->prices()->create(['value' => 0]);

        $this->actingAs($this->$user)
            ->json('GET', '/shipping-methods', ['cart_value' => 1200])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['price' => 10]);
    }
}
