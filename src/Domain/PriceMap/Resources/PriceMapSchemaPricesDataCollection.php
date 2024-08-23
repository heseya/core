<?php

declare(strict_types=1);

namespace Domain\PriceMap\Resources;

use Domain\PriceMap\PriceMap;
use Domain\PriceMap\PriceMapSchemaOptionPrice;
use Domain\ProductSchema\Models\Schema;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Spatie\LaravelData\DataCollection;

/**
 * @extends DataCollection<int,PriceMapSchemaPricesData>
 */
final class PriceMapSchemaPricesDataCollection extends DataCollection
{
    /**
     * @return PriceMapSchemaPricesDataCollection<int,PriceMapSchemaPricesData>
     */
    public static function fromSchema(Schema $schema): static
    {
        $items = [];
        foreach (PriceMap::all() as $priceMap) {
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

        return new self(items: $items);
    }

    /**
     * @param DataCollection<int,PriceMapSchemaOptionPrice>|array<int,PriceMapSchemaOptionPrice>|array<int,array<string,mixed>> $items
     */
    public function __construct(
        string $dataClass = PriceMapSchemaPricesData::class,
        array|DataCollection|Enumerable|null $items = null,
    ) {
        $dataClass = PriceMapSchemaPricesData::class;
        parent::__construct($dataClass, $items);
    }
}
