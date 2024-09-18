<?php

declare(strict_types=1);

namespace Domain\PriceMap\Listeners;

use App\Events\ProductCreated;
use App\Services\ProductService;
use Domain\PriceMap\PriceMap;
use Domain\PriceMap\PriceMapProductPrice;
use Illuminate\Support\Str;

final class CreatePricesInPriceMapsAfterProductCreated
{
    public function handle(ProductCreated $event): void
    {
        $insert = [];
        foreach (PriceMap::all() as $priceMap) {
            $insert[] = [
                'id' => Str::orderedUuid()->toString(),
                'price_map_id' => $priceMap->getKey(),
                'product_id' => $event->getProduct()->getKey(),
                'currency' => $priceMap->currency->value,
                'is_net' => $priceMap->is_net,
                'value' => 0,
            ];
        }
        PriceMapProductPrice::query()->insertOrIgnore($insert);

        app(ProductService::class)->updateMinPrices($event->getProduct());
    }
}
