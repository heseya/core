<?php

namespace Tests\Traits;

use App\Enums\ShippingType;
use App\Models\ShippingMethod;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domains\Currency\Currency;

trait CreateShippingMethod
{
    protected ShippingMethod $shippingMethod;

    /**
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     */
    public function createShippingMethod(
        float $price = 0,
        array $payload = ['shipping_type' => ShippingType::ADDRESS]
    ): ShippingMethod {
        $this->shippingMethod = ShippingMethod::factory()->create($payload);
        $this->shippingMethod->priceRanges()->create([
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'start' => Money::zero(Currency::DEFAULT->value),
            'value' => Money::of($price, Currency::DEFAULT->value),
        ]);

        return $this->shippingMethod;
    }
}
