<?php

declare(strict_types=1);

namespace Domain\PriceMap\Listeners;

use App\Events\OptionCreated;
use Domain\PriceMap\PriceMap;
use Domain\PriceMap\PriceMapSchemaOptionPrice;

final class CreatePricesInPriceMapsAfterOptionCreated
{
    public function handle(OptionCreated $event): void
    {
        $insert = [];
        foreach (PriceMap::all() as $priceMap) {
            $insert[] = [
                'price_map_id' => $priceMap->getKey(),
                'option_id' => $event->getOption()->getKey(),
                'currency' => $priceMap->currency,
                'is_net' => $priceMap->is_net,
                'value' => 0,
            ];
        }
        PriceMapSchemaOptionPrice::query()->insert($insert);
    }
}
