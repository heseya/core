<?php

namespace Tests\Traits;

use App\Enums\ShippingType;
use App\Models\ShippingMethod;

trait CreateShippingMethod
{
    protected ShippingMethod $shippingMethod;

    public function createShippingMethod(
        float $price = 0,
        array $payload = ['shipping_type' => ShippingType::ADDRESS]
    ): ShippingMethod {
        $this->shippingMethod = ShippingMethod::factory()->create($payload);
        $priceRange = $this->shippingMethod->priceRanges()->create([
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'start' => 0,
        ]);
        $priceRange->prices()->create([
            'value' => $price,
        ]);

        return $this->shippingMethod;
    }
}
