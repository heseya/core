<?php

declare(strict_types=1);

namespace Domain\PriceMap;

use App\Models\Option;
use App\Models\Product;
use App\Traits\GetPublishedLanguageFilter;
use Domain\Currency\Currency;
use Domain\Price\Dtos\PriceDto;
use Domain\PriceMap\Dtos\PriceMapCreateDto;
use Domain\PriceMap\Dtos\PriceMapPricesUpdateDto;
use Domain\PriceMap\Dtos\PriceMapPriceUpdateDto;
use Domain\PriceMap\Dtos\PriceMapProductPricesUpdateDto;
use Domain\PriceMap\Dtos\PriceMapProductPricesUpdatePartialDto;
use Domain\PriceMap\Dtos\PriceMapSchemaPricesUpdateDto;
use Domain\PriceMap\Dtos\PriceMapSchemaPricesUpdateOptionDto;
use Domain\PriceMap\Dtos\PriceMapSchemaPricesUpdatePartialDto;
use Domain\PriceMap\Dtos\PriceMapUpdateDto;
use Domain\PriceMap\Jobs\CreatePricesForAllProductsAndOptionsJob;
use Domain\PriceMap\Jobs\RefreshCachedPricesForSalesChannel;
use Domain\PriceMap\Resources\PriceMapData;
use Domain\PriceMap\Resources\PriceMapPricesForProductData;
use Domain\PriceMap\Resources\PriceMapProductPriceData;
use Domain\PriceMap\Resources\PriceMapSchemaPricesDataCollection;
use Domain\PriceMap\Resources\PriceMapUpdatedPricesData;
use Domain\Product\Dtos\ProductSearchDto;
use Domain\ProductSchema\Models\Schema;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
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
        $priceMap->refresh();

        CreatePricesForAllProductsAndOptionsJob::dispatch($priceMap);

        return $priceMap;
    }

    public function createPricesForAllMissingProductsAndSchemas(PriceMap $priceMap): void
    {
        Product::query()
            ->whereNotIn('id', $priceMap->productPrices()->pluck('product_id'))
            ->select('id')
            ->chunkById(1000, function (Collection $products) use ($priceMap): void {
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
            ->chunkById(1000, function (Collection $options) use ($priceMap): void {
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
        if (in_array($priceMap->getKey(), Currency::defaultPriceMapIds(), true)) {
            $dto->currency = $priceMap->currency->value; // unable to change default price map currency
        }

        $priceMap->fill($dto->toArray())->save();

        if ($priceMap->wasChanged(['currency', 'is_net'])) {
            $priceMap->productPrices()->update([
                'currency' => $priceMap->currency->value,
                'is_net' => $priceMap->is_net,
            ]);
            $priceMap->schemaOptionsPrices()->update([
                'currency' => $priceMap->currency->value,
                'is_net' => $priceMap->is_net,
            ]);
            foreach ($priceMap->salesChannels as $salesChannel) {
                dispatch(new RefreshCachedPricesForSalesChannel($salesChannel));
            }
        }

        return $priceMap;
    }

    public function delete(PriceMap $priceMap): void
    {
        if (in_array($priceMap->getKey(), Currency::defaultPriceMapIds(), true)) {
            throw new Exception('Unable to delete default price map');
        }

        $priceMap->delete();
    }

    /**
     * @return Collection<int,PriceMap> $priceMaps
     */
    public function listDefault(): Collection
    {
        return Cache::driver('array')->rememberForever('default_price_maps', fn () => PriceMap::query()->whereIn('id', Currency::defaultPriceMapIds())->get());
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
                    'value' => ((float) $priceDto->value) * 100,
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
                    'value' => ((float) $priceDto->value) * 100,
                    'currency' => $priceMap->currency,
                    'is_net' => $priceMap->is_net,
                ]);
            }
        }

        /** @var Builder<Product> $query */
        $query = Product::query()
            ->whereIn('id', array_column($updated_products, 'product_id'))
            ->orWhereHas('schemas', fn (Builder $q) => $q->whereIn('id', array_column($updated_options, 'schema_id')))
            ->with([
                'mapPrices' => fn (Builder|HasMany $productsubquery) => $productsubquery->where('price_map_id', '=', $priceMap->id),
                'schemas',
                'schemas.options',
                'schemas.options.mapPrices' => fn (Builder|HasMany $optionsubquery) => $optionsubquery->where('price_map_id', '=', $priceMap->id),
            ]);

        return PriceMapUpdatedPricesData::from([
            'data' => $query->get(),
        ])->withoutWrapping();
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
            PriceMapProductPrice::query()->where(['price_map_id' => $partial->price_map_id, 'product_id' => $product->id])->update(['value' => ((float) $partial->price) * 100]);
        }

        return PriceMapProductPriceData::collection($product->mapPrices);
    }

    /**
     * @param DataCollection<int,PriceDto>|array<int,PriceMap> $priceDtos
     */
    public function updateProductPricesForDefaultMaps(Product|string $product, array|DataCollection $priceDtos): void
    {
        if ($priceDtos instanceof DataCollection && $priceDtos->dataClass !== PriceDto::class) {
            throw new InvalidArgumentException();
        }
        if (is_array($priceDtos)) {
            $priceDtos = PriceDto::collection($priceDtos);
        }

        $data = $priceDtos->toCollection()->map(
            function (PriceDto $priceDto) use ($product) {
                $priceMap = $this->listDefault()->where('id', $priceDto->currency->getDefaultPriceMapId())->firstOrFail();

                return [
                    'id' => Uuid::uuid6()->toString(),
                    'price_map_id' => $priceMap->id,
                    'product_id' => $product instanceof Product ? $product->id : $product,
                    'value' => $priceDto->value->getMinorAmount()->toInt(),
                    'currency' => $priceMap->currency->value,
                    'is_net' => $priceMap->is_net,
                ];
            },
        )->toArray();

        PriceMapProductPrice::query()->upsert($data, ['price_map_id', 'product_id'], ['value', 'currency', 'is_net']);
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
                PriceMapSchemaOptionPrice::query()->where(['price_map_id' => $partial->price_map_id, 'option_id' => $option->id])->update(['value' => ((float) $option->price) * 100]);
            }
        }

        return PriceMapSchemaPricesDataCollection::fromSchema($schema);
    }

    /**
     * @param DataCollection<int,PriceDto> $priceDtos
     */
    public function updateOptionPricesForDefaultMaps(Option $option, DataCollection $priceDtos): void
    {
        if ($priceDtos instanceof DataCollection && $priceDtos->dataClass !== PriceDto::class) {
            throw new InvalidArgumentException();
        }

        $data = $priceDtos->toCollection()->map(
            function (PriceDto $priceDto) use ($option) {
                $priceMap = $this->listDefault()->where('id', $priceDto->currency->getDefaultPriceMapId())->firstOrFail();

                return [
                    'id' => Uuid::uuid6(),
                    'price_map_id' => $priceMap->id,
                    'option_id' => $option->id,
                    'value' => $priceDto->value->getMinorAmount(),
                    'currency' => $priceMap->currency->value,
                    'is_net' => $priceMap->is_net,
                ];
            },
        )->toArray();

        PriceMapSchemaOptionPrice::query()->upsert($data, ['price_map_id', 'option_id'], ['value', 'currency', 'is_net']);
    }
}
