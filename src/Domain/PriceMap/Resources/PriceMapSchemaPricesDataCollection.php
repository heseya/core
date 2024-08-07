<?php

declare(strict_types=1);

namespace Domain\PriceMap\Resources;

use Domain\PriceMap\PriceMap;
use Domain\ProductSchema\Models\Schema;
use Spatie\LaravelData\DataCollection;

/**
 * @extends DataCollection<int,PriceMapSchemaPricesData>
 */
final class PriceMapSchemaPricesDataCollection extends DataCollection
{
    public function __construct(Schema $schema)
    {
        $priceMaps = PriceMap::all();

        $items = [];

        foreach ($priceMaps as $priceMap) {
            /*
            $prices = $schema->mapPrices->where('price_map_id', '=', $priceMap->id);

            $options = [];
            foreach ($prices as $price) {
                $options[] = [
                    'id' => $price->option_id,
                    'price' => $price->value,
                ];
            }
                */

            $items[] = [
                'price_map_id' => $priceMap->id,
                'price_map_name' => $priceMap->name,
                'currency' => $priceMap->currency,
                'is_net' => $priceMap->is_net,
                'options' => $schema->mapPrices->where('price_map_id', '=', $priceMap->id)->toArray(),
            ];
        }

        parent::__construct(PriceMapSchemaPricesData::class, $items);
    }
}
