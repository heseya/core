<?php

declare(strict_types=1);

namespace Domain\PriceMap\Listeners;

use App\Events\ProductCreated;
use Domain\PriceMap\PriceMap;
use Domain\PriceMap\PriceMapProductPrice;

final class ProductCreatedListener
{
    public function handle(ProductCreated $event): void
    {
        foreach (PriceMap::all() as $priceMap) {
            PriceMapProductPrice::create([
                'price_map_id' => $priceMap->getKey(),
                'product_id' => $event->getProductId(),
                'currency' => $priceMap->currency,
                'is_net' => $priceMap->is_net,
                'value' => 0
            ]);
        }
    }
}
