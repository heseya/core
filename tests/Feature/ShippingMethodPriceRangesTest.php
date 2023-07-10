<?php

namespace Tests\Feature;

use App\Enums\Currency;
use App\Models\PriceRange;
use App\Models\ShippingMethod;
use Brick\Money\Money;
use Tests\TestCase;

class ShippingMethodPriceRangesTest extends TestCase
{
    /**
     * @dataProvider authProvider
     */
    public function testIndexByPrice($user): void
    {
        $this->{$user}->givePermissionTo('shipping_methods.show');

        $shippingMethod = ShippingMethod::factory()->create([
            'public' => true,
            'block_list' => false,
        ]);

        $currency = Currency::DEFAULT->value;

        $shippingMethod->priceRanges()->saveMany([
            $priceRange1 = PriceRange::make([
                'start' => Money::zero($currency),
                'value' => Money::of(20, $currency),
            ]),
            $priceRange2 = PriceRange::make([
                'start' => Money::of(1000, $currency),
                'value' => Money::of(10, $currency),
            ]),
            $priceRange3 = PriceRange::make([
                'start' => Money::of(1500, $currency),
                'value' => Money::zero($currency),
            ]),
        ]);

        $this->actingAs($this->{$user})
            ->json('GET', '/shipping-methods', ['cart_value' => '1200.00'])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['price' => ['value' => '10.00']]);
    }
}
