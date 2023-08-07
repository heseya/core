<?php

namespace Tests\Feature;

use App\Models\PriceRange;
use App\Models\ShippingMethod;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Tests\TestCase;

class ShippingMethodPriceRangesTest extends TestCase
{
    /**
     * @dataProvider authProvider
     *
     * @throws NumberFormatException
     * @throws UnknownCurrencyException
     * @throws RoundingNecessaryException
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
            PriceRange::query()->make([
                'start' => Money::zero($currency),
                'value' => Money::of(20, $currency),
            ]),
            PriceRange::query()->make([
                'start' => Money::of(1000, $currency),
                'value' => Money::of(10, $currency),
            ]),
            PriceRange::query()->make([
                'start' => Money::of(1500, $currency),
                'value' => Money::zero($currency),
            ]),
        ]);

        $this->actingAs($this->{$user})
            ->json('GET', '/shipping-methods', ['cart_value' => [
                'value' => '1200.00',
                'currency' => $currency,
            ]])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['prices' => [[
                'gross' => '10.00',
                'currency' => $currency,
            ]]]);
    }
}
