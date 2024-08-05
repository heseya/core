<?php

declare(strict_types=1);

namespace Domain\PriceMap;

use App\Models\Option;
use App\Models\Product;
use App\Traits\GetPublishedLanguageFilter;
use Domain\PriceMap\Dtos\PriceMapCreateDto;
use Domain\PriceMap\Dtos\PriceMapUpdateDto;
use Domain\PriceMap\PriceMap;
use Domain\PriceMap\PriceMapProductPrice;
use Domain\PriceMap\PriceMapSchemaOptionPrice;
use Illuminate\Database\Eloquent\Collection;

final readonly class PriceMapService
{
    use GetPublishedLanguageFilter;

    /**
     * @return Collection<int,PriceMap>
     */
    public function list(): Collection
    {
        return PriceMap::all();
    }

    public function create(PriceMapCreateDto $dto): PriceMap
    {
        $priceMap = new PriceMap($dto->toArray());
        $priceMap->save();

        $this->createPricesForAllMissingProductsAndSchemas($priceMap);

        return $priceMap;
    }

    public function createPricesForAllMissingProductsAndSchemas(PriceMap $priceMap): void
    {
        PriceMapProductPrice::insert(
            Product::query()
                ->whereNotIn('id', $priceMap->productPrices()->pluck('product_id'))
                ->pluck('id')
                ->map(fn (string $uuid) => [
                    'currency' => $priceMap->currency,
                    'is_net' => $priceMap->is_net,
                    'price_map_id' => $priceMap->id,
                    'product_id' => $uuid,
                    'value' => 0,
                ])->toArray(),
        );

        PriceMapSchemaOptionPrice::insert(
            Option::query()
                ->whereNotIn('id', $priceMap->schemaOptionsPrices()->pluck('option_id'))
                ->pluck('id')
                ->map(fn (string $uuid) => [
                    'currency' => $priceMap->currency,
                    'is_net' => $priceMap->is_net,
                    'option_id' => $uuid,
                    'price_map_id' => $priceMap->id,
                    'value' => 0,
                ])->toArray(),
        );
    }

    public function update(PriceMap $priceMap, PriceMapUpdateDto $dto): PriceMap
    {
        $priceMap->fill($dto->toArray())->save();

        return $priceMap;
    }

    public function delete(PriceMap $priceMap): void
    {
        $priceMap->delete();
    }
}
