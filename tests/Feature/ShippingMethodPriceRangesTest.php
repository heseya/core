<?php

namespace Tests\Feature;

use App\Models\PriceRange;
use App\Models\ShippingMethod;
use Tests\TestCase;

class ShippingMethodPriceRangesTest extends TestCase
{
    public function testIndexByPrice(): void
    {
        $shippingMethod = ShippingMethod::factory()->create([
            'public' => true,
            'black_list' => false,
        ]);

        $shippingMethod->priceRanges()->saveMany([
            $priceRange1 = PriceRange::make(['start' => 0]),
            $priceRange2 = PriceRange::make(['start' => 1000]),
            $priceRange3 = PriceRange::make(['start' => 1500]),
        ]);

        $priceRange1->prices()->create(['value' => 20]);
        $priceRange2->prices()->create(['value' => 10]);
        $priceRange3->prices()->create(['value' => 0]);

        $this->postJson('/shipping-methods/filter', ['cart_value' => 1200])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['price' => 10]);
    }
}
