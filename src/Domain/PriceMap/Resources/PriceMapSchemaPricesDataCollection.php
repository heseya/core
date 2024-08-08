<?php

declare(strict_types=1);

namespace Domain\PriceMap\Resources;

use Domain\PriceMap\PriceMap;
use Domain\PriceMap\PriceMapSchemaOptionPrice;
use Domain\ProductSchema\Models\Schema;
use Illuminate\Support\Collection;
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
            /** @var Collection<int,PriceMapSchemaOptionPrice> $mapPrices */
            $mapPrices = $schema->mapPrices->where('price_map_id', '=', $priceMap->id);
            $items[] = [
                'price_map_id' => $priceMap->id,
                'price_map_name' => $priceMap->name,
                'currency' => $priceMap->currency->value,
                'is_net' => $priceMap->is_net,
                'options' => $mapPrices->values()->all(),
            ];
        }

        parent::__construct(PriceMapSchemaPricesData::class, $items);
    }
}
