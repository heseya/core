<?php

declare(strict_types=1);

namespace Domain\PriceMap;

use App\Models\Option;
use App\Models\Product;
use App\Traits\GetPublishedLanguageFilter;
use Domain\Currency\Currency;
use Domain\PriceMap\Dtos\PriceMapCreateDto;
use Domain\PriceMap\Dtos\PriceMapPricesUpdateDto;
use Domain\PriceMap\Dtos\PriceMapPriceUpdateDto;
use Domain\PriceMap\Dtos\PriceMapProductPricesUpdateDto;
use Domain\PriceMap\Dtos\PriceMapProductPricesUpdatePartialDto;
use Domain\PriceMap\Dtos\PriceMapSchemaPricesUpdateDto;
use Domain\PriceMap\Dtos\PriceMapSchemaPricesUpdateOptionDto;
use Domain\PriceMap\Dtos\PriceMapSchemaPricesUpdatePartialDto;
use Domain\PriceMap\Dtos\PriceMapUpdateDto;
use Domain\PriceMap\Resources\PriceMapData;
use Domain\PriceMap\Resources\PriceMapPricesForProductData;
use Domain\PriceMap\Resources\PriceMapProductPriceData;
use Domain\PriceMap\Resources\PriceMapSchemaPricesDataCollection;
use Domain\PriceMap\Resources\PriceMapUpdatedPricesData;
use Domain\Product\Dtos\ProductSearchDto;
use Domain\ProductSchema\Models\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\PaginatedDataCollection;

final readonly class PriceMapService
{
    use GetPublishedLanguageFilter;

    /**
     * @return PaginatedDataCollection<int|string,PriceMapData>
     */
    public function list(): PaginatedDataCollection
    {
        return PriceMapData::collection(PriceMap::query()->paginate());
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
        Product::query()
            ->whereNotIn('id', $priceMap->productPrices()->pluck('product_id'))
            ->select('id')
            ->chunk(1000, function (Collection $products) use ($priceMap): void {
                PriceMapProductPrice::insert(
                    $products->pluck('id')->map(fn (string $uuid) => [
                        'id' => Str::orderedUuid()->toString(),
                        'currency' => $priceMap->currency,
                        'is_net' => $priceMap->is_net,
                        'price_map_id' => $priceMap->id,
                        'product_id' => $uuid,
                        'value' => 0,
                    ])->toArray(),
                );
            });

        Option::query()
            ->whereNotIn('id', $priceMap->schemaOptionsPrices()->pluck('option_id'))
            ->select('id')
            ->chunk(1000, function (Collection $options) use ($priceMap): void {
                PriceMapSchemaOptionPrice::insert(
                    $options->pluck('id')->map(fn (string $uuid) => [
                        'id' => Str::orderedUuid()->toString(),
                        'currency' => $priceMap->currency,
                        'is_net' => $priceMap->is_net,
                        'option_id' => $uuid,
                        'price_map_id' => $priceMap->id,
                        'value' => 0,
                    ])->toArray(),
                );
            });
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

    public function updatePrices(PriceMap $priceMap, PriceMapPricesUpdateDto $dto): PriceMapUpdatedPricesData
    {
        $updated_products = [];
        if ($dto->products instanceof DataCollection) {
            /** @var PriceMapPriceUpdateDto $priceDto */
            foreach ($dto->products as $priceDto) {
                $updated_products[] = PriceMapProductPrice::query()->updateOrCreate([
                    'product_id' => $priceDto->id,
                    'price_map_id' => $priceMap->id,
                ], [
                    'value' => $priceDto->value,
                    'currency' => $priceMap->currency,
                    'is_net' => $priceMap->is_net,
                ]);
            }
        }
        $updated_options = [];
        if ($dto->schema_options instanceof DataCollection) {
            /** @var PriceMapPriceUpdateDto $priceDto */
            foreach ($dto->schema_options as $priceDto) {
                $updated_options[] = PriceMapSchemaOptionPrice::query()->updateOrCreate([
                    'option_id' => $priceDto->id,
                    'price_map_id' => $priceMap->id,
                ], [
                    'value' => $priceDto->value,
                    'currency' => $priceMap->currency,
                    'is_net' => $priceMap->is_net,
                ]);
            }
        }

        return PriceMapUpdatedPricesData::from([
            'products' => $updated_products,
            'schema_options' => $updated_options,
        ]);
    }

    /**
     * @return PaginatedDataCollection<int|string,PriceMapPricesForProductData>
     */
    public function searchPrices(PriceMap $priceMap, ProductSearchDto $dto): PaginatedDataCollection
    {
        if (Config::get('search.use_scout') && is_string($dto->search) && !empty($dto->search)) {
            $scoutResults = Product::search($dto->search)->keys()->toArray();
            $dto->search = new Optional();
            $dto->ids = is_array($dto->ids) && !empty($dto->ids)
                ? array_intersect($scoutResults, $dto->ids)
                : $scoutResults;
        }

        /** @var Builder<Product> $query */
        $query = Product::searchByCriteria($dto->except('sort')->toArray() + $this->getPublishedLanguageFilter('products'))
            ->with([
                'mapPrices' => fn (Builder|HasMany $productsubquery) => $productsubquery->where('price_map_id', '=', $priceMap->id),
                'schemas',
                'schemas.options',
                'schemas.options.mapPrices' => fn (Builder|HasMany $optionsubquery) => $optionsubquery->where('price_map_id', '=', $priceMap->id),
            ]);

        if (is_string($dto->price_sort_direction)) {
            if ($dto->price_sort_direction === 'price:asc') {
                $query->withMin([
                    'pricesMin as price' => fn (Builder $subquery) => $subquery->where(
                        'currency',
                        $dto->price_sort_currency ?? Currency::DEFAULT->value,
                    ),
                ], 'value');
            }
            if ($dto->price_sort_direction === 'price:desc') {
                $query->withMax([
                    'pricesMax as price' => fn (Builder $subquery) => $subquery->where(
                        'currency',
                        $dto->price_sort_currency ?? Currency::DEFAULT->value,
                    ),
                ], 'value');
            }
        }

        if (Config::get('search.use_scout') && !empty($scoutResults)) {
            $query->orderByRaw('FIELD(products.id,"' . implode('","', $scoutResults) . '")');
        }

        if (is_string($dto->sort)) {
            $query->reorder();
            $query->sort($dto->sort);
        }

        $products = $query->paginate();

        return PriceMapPricesForProductData::collection($products);
    }

    /**
     * @return DataCollection<int,PriceMapProductPriceData>
     */
    public function updateProductPrices(Product $product, PriceMapProductPricesUpdateDto $dto): DataCollection
    {
        /**
         * @var PriceMapProductPricesUpdatePartialDto $partial
         */
        foreach ($dto->prices as $partial) {
            PriceMapProductPrice::query()->where(['price_map_id' => $partial->price_map_id, 'product_id' => $product->id])->update(['value' => $partial->price]);
        }

        return PriceMapProductPriceData::collection($product->mapPrices);
    }

    public function updateSchemaPrices(Schema $schema, PriceMapSchemaPricesUpdateDto $dto): PriceMapSchemaPricesDataCollection
    {
        /**
         * @var PriceMapSchemaPricesUpdatePartialDto $partial
         */
        foreach ($dto->prices as $partial) {
            /**
             * @var PriceMapSchemaPricesUpdateOptionDto $option
             */
            foreach ($partial->options as $option) {
                PriceMapSchemaOptionPrice::query()->where(['price_map_id' => $partial->price_map_id, 'option_id' => $option->id])->update(['value' => $option->price]);
            }
        }

        return new PriceMapSchemaPricesDataCollection($schema);
    }
}
