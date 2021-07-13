<?php

namespace Tests\Traits;

use App\Models\ShippingMethod;

trait CreateShippingMethod
{
    public function createShippingMethod(float $price = 0, array $payload = []): ShippingMethod
    {
        $shippingMethod = ShippingMethod::factory()->create($payload);
        $priceRange = $shippingMethod->priceRanges()->create([
            'shipping_method_id' => $shippingMethod->getKey(),
            'start' => 0,
        ]);
        $priceRange->prices()->create([
            'value' => $price,
        ]);

        return $shippingMethod;
    }
}
