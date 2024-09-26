<?php

declare(strict_types=1);

namespace Domain\PriceMap;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ServerException;
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
use Domain\PriceMap\Jobs\RefreshCachedPricesForProductAndPriceMaps;
use Domain\PriceMap\Jobs\RefreshCachedPricesForSalesChannel;
use Domain\PriceMap\Resources\PriceMapData;
use Domain\PriceMap\Resources\PriceMapPricesForProductData;
use Domain\PriceMap\Resources\PriceMapProductPriceData;
use Domain\PriceMap\Resources\PriceMapSchemaPricesDataCollection;
use Domain\PriceMap\Resources\PriceMapUpdatedPricesData;
use Domain\Product\Dtos\ProductSearchDto;
use Domain\ProductSchema\Models\Schema;
use Domain\SalesChannel\Models\SalesChannel;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
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
        return PriceMapData::collection(PriceMap::query()->paginate(Config::get('pagination.per_page')));
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

        $product_ids = Arr::map($updated_products, fn (PriceMapProductPrice $product) => $product->product_id);
        $option_ids = Arr::map($updated_options, fn (PriceMapSchemaOptionPrice $option) => $option->option_id);
        if (!empty($option_ids)) {
            $product_ids = array_merge($product_ids, Product::query()->whereHas('schemas', fn (Builder $query) => $query->whereHas('options', fn (Builder $subquery) => $subquery->whereIn('id', $option_ids)))->pluck('id')->toArray());
        }
        $product_ids = array_unique($product_ids);

        foreach ($product_ids as $product_id) {
            dispatch(new RefreshCachedPricesForProductAndPriceMaps($product_id, [$priceMap->id]));
        }

        /** @var Builder<Product> $query */
        $query = Product::query()
            ->whereIn('id', $product_ids)
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
                    'pricesMin as price' => fn (Builder $subquery) => $subquery->where(
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

        $products = $query->paginate(Config::get('pagination.per_page'));

        return PriceMapPricesForProductData::collection($products);
    }

    public function createProductPrices(Product $product): void
    {
        $price_map_ids = $product->mapPrices()->pluck('price_map_id')->toArray();

        $insert = [];
        foreach (PriceMap::whereNotIn('id', $price_map_ids)->get() as $priceMap) {
            $insert[] = [
                'id' => Str::orderedUuid()->toString(),
                'price_map_id' => $priceMap->getKey(),
                'product_id' => $product->getKey(),
                'currency' => $priceMap->currency->value,
                'value' => 0,
                'is_net' => $priceMap->is_net,
            ];
        }

        PriceMapProductPrice::insert($insert);
    }

    /**
     * @return DataCollection<int,PriceMapProductPriceData>
     */
    public function updateProductPrices(Product $product, PriceMapProductPricesUpdateDto $dto): DataCollection
    {
        $price_map_ids = [];

        /**
         * @var PriceMapProductPricesUpdatePartialDto $partial
         */
        foreach ($dto->prices as $partial) {
            $price_map_ids[] = $partial->price_map_id;
            PriceMapProductPrice::query()->where(['price_map_id' => $partial->price_map_id, 'product_id' => $product->id])->update(['value' => ((float) $partial->price) * 100]);
        }

        dispatch(new RefreshCachedPricesForProductAndPriceMaps($product->getKey(), $price_map_ids));

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
                    'product_id' => $product instanceof Product ? $product->getKey() : $product,
                    'value' => $priceDto->value->getMinorAmount()->toInt(),
                    'currency' => $priceMap->currency->value,
                    'is_net' => $priceMap->is_net,
                ];
            },
        )->toArray();

        PriceMapProductPrice::query()->upsert($data, ['price_map_id', 'product_id'], ['value', 'currency', 'is_net']);

        dispatch(new RefreshCachedPricesForProductAndPriceMaps($product instanceof Product ? $product->getKey() : $product, $priceDtos->toCollection()->map(fn (PriceDto $priceDto) => $priceDto->currency->getDefaultPriceMapId())->toArray()));
    }

    public function updateSchemaPrices(Schema $schema, PriceMapSchemaPricesUpdateDto $dto): PriceMapSchemaPricesDataCollection
    {
        $price_map_ids = [];

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

            $price_map_ids[] = $partial->price_map_id;
        }

        dispatch(new RefreshCachedPricesForProductAndPriceMaps($schema->product_id, $price_map_ids));

        return PriceMapSchemaPricesDataCollection::fromSchema($schema);
    }

    public function createOptionPrices(Option $option): void
    {
        $price_map_ids = $option->mapPrices()->pluck('price_map_id')->toArray();

        $insert = [];
        foreach (PriceMap::whereNotIn('id', $price_map_ids)->get() as $priceMap) {
            $insert[] = [
                'id' => Str::orderedUuid()->toString(),
                'price_map_id' => $priceMap->getKey(),
                'option_id' => $option->getKey(),
                'currency' => $priceMap->currency->value,
                'value' => 0,
                'is_net' => $priceMap->is_net,
            ];
        }

        PriceMapSchemaOptionPrice::insert($insert);
    }

    /**
     * @param DataCollection<int,PriceDto> $priceDtos
     */
    public function updateOptionPricesForDefaultMaps(Option $option, DataCollection $priceDtos, bool $is_default = false): void
    {
        if ($priceDtos instanceof DataCollection && $priceDtos->dataClass !== PriceDto::class) {
            throw new InvalidArgumentException();
        }

        $data = $priceDtos->toCollection()->map(
            function (PriceDto $priceDto) use ($option, $is_default) {
                $priceMap = $this->listDefault()->where('id', $priceDto->currency->getDefaultPriceMapId())->firstOrFail();

                return [
                    'id' => Uuid::uuid6(),
                    'price_map_id' => $priceMap->id,
                    'option_id' => $option->id,
                    'value' => $is_default
                        ? 0
                        : $priceDto->value->getMinorAmount(),
                    'currency' => $priceMap->currency->value,
                    'is_net' => $priceMap->is_net,
                ];
            },
        )->toArray();

        PriceMapSchemaOptionPrice::query()->upsert($data, ['price_map_id', 'option_id'], ['value', 'currency', 'is_net']);

        if ($option->schema !== null && $option->schema->product_id !== null) {
            dispatch(new RefreshCachedPricesForProductAndPriceMaps($option->schema->product_id, $priceDtos->toCollection()->map(fn (PriceDto $priceDto) => $priceDto->currency->getDefaultPriceMapId())->toArray()));
        }
    }

    /**
     * @return ($model is Product ? PriceMapProductPrice : PriceMapSchemaOptionPrice)
     */
    public function getOrCreateMappedPriceForPriceMap(Option|Product $model, PriceMap|string $priceMap): PriceMapProductPrice|PriceMapSchemaOptionPrice
    {
        /** @var PriceMap $priceMap */
        $priceMap = $priceMap instanceof PriceMap ? $priceMap : PriceMap::query()->whereKey($priceMap)->firstOrFail();

        if ($model->relationLoaded('mapPrices')) {
            /** @var Collection<int,PriceMapProductPrice> $mapPrices */
            $mapPrices = $model->mapPrices->where('price_map_id', $priceMap->getKey())->where('currency', '=', $priceMap->currency);
        } else {
            /** @var Builder<PriceMapProductPrice> $mapPrices */
            $mapPrices = $model->mapPrices()->ofPriceMap($priceMap);
        }

        try {
            $price = $mapPrices->firstOrFail();
        } catch (Exception $ex) {
            /** @var PriceMapProductPrice|PriceMapSchemaOptionPrice $price */
            $price = $model->mapPrices()->firstOrCreate([
                'price_map_id' => $priceMap->getKey(),
            ], [
                'value' => 0,
                'currency' => $priceMap->currency->value,
                'is_net' => $priceMap->is_net,
            ]);
        }

        $model->mapPrices()->where('price_map_id', '=', $priceMap->getKey())->where('id', '!=', $price->id)->delete();

        if ($price->currency !== $priceMap->currency) {
            // if Price for this Price map exists, but has wrong currency
            $price->currency = $priceMap->currency;
            $price->save();
        }

        return $price;
    }

    /**
     * @return ($model is Product ? PriceMapProductPrice : PriceMapSchemaOptionPrice)
     *
     * @throws ServerException
     */
    public function getOrCreateMappedPriceForSalesChannel(Option|Product $model, SalesChannel|string $salesChannel): PriceMapProductPrice|PriceMapSchemaOptionPrice
    {
        $salesChannel = $salesChannel instanceof SalesChannel ? $salesChannel : SalesChannel::findOrFail($salesChannel);
        assert($salesChannel instanceof SalesChannel);
        $priceMap = $salesChannel->priceMap;

        if ($priceMap === null) {
            throw new ServerException(Exceptions::CLIENT_SALES_CHANNEL_PRICE_MAP);
        }

        return $this->getOrCreateMappedPriceForPriceMap($model, $priceMap);
    }
}
