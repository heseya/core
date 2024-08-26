<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Product;
use App\Traits\GetPublishedLanguageFilter;
use Domain\Currency\Currency;
use Domain\PriceMap\PriceMap;
use Domain\Product\Dtos\ProductSearchDto;
use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelData\Optional;

class ProductRepository
{
    use GetPublishedLanguageFilter;

    public function search(ProductSearchDto $dto, SalesChannel $salesChannel = null): LengthAwarePaginator
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
                'media',
                'media.metadata',
                'media.metadataPrivate',
                'publishedTags',
                'metadata',
                'metadataPrivate',
            ]);

        $priceMap = $salesChannel?->priceMap ?? PriceMap::findOrFail($dto->getCurrency()->getDefaultPriceMapId());

        assert($priceMap instanceof PriceMap);

        $query->with(['pricesMin' => fn (Builder|MorphMany $morphMany) => $salesChannel ? $morphMany->where('sales_channel_id', $salesChannel->id) : $morphMany->where('currency', $priceMap->currency)]);
        $query->with(['pricesMinInitial' => fn (Builder|MorphMany $morphMany) => $salesChannel ? $morphMany->where('sales_channel_id', $salesChannel->id) : $morphMany->where('currency', $priceMap->currency)]);
        $query->with(['mapPrices' => fn (Builder|HasMany $hasMany) => $hasMany->where('price_map_id', $priceMap->id)]);

        if (is_bool($dto->full) && $dto->full) {
            $query->with([
                'items',
                'schemas',
                'schemas.options',
                'schemas.options.schema',
                'schemas.options.items',
                'schemas.options.metadata',
                'schemas.options.metadataPrivate',
                'schemas.options.prices',
                'schemas.metadata',
                'schemas.metadataPrivate',
                'sets',
                'sets.metadata',
                'sets.metadataPrivate',
                'sets.media',
                'sets.media.metadata',
                'sets.media.metadataPrivate',
                'sets.childrenPublic',
                'sets.parent',
                'relatedSets',
                'relatedSets.media',
                'relatedSets.media.metadata',
                'relatedSets.media.metadataPrivate',
                'relatedSets.metadata',
                'relatedSets.metadataPrivate',
                'relatedSets.childrenPublic',
                'relatedSets.parent',
                'sales.metadata',
                'sales.metadataPrivate',
                'sales.amounts',
                'pages',
                'pages.metadata',
                'pages.metadataPrivate',
                'attachments',
                'attachments.media',
                'attachments.media.metadata',
                'attachments.media.metadataPrivate',
                'seo',
                'seo.media',
                'seo.media.metadata',
                'seo.media.metadataPrivate',
            ]);
            $query->with(['sales' => fn (BelongsToMany|Builder $hasMany) => $hasMany->withOrdersCount()]); // @phpstan-ignore-line
            $query->with(['schemas.options.mapPrices' => fn (Builder|HasMany $hasMany) => $hasMany->where('price_map_id', $priceMap->id)]);
        }

        if (Gate::denies('products.show_hidden')) {
            $query->where('products.public', true);
        }

        if (request()->filled('attribute_slug')) {
            $query->with([
                'productAttributes' => fn (Builder|HasMany $subquery) => $subquery->slug(explode(';', request()->input('attribute_slug'))), // @phpstan-ignore-line
                'productAttributes.attribute',
                'productAttributes.attribute.metadata',
                'productAttributes.attribute.metadataPrivate',
                'productAttributes.options',
                'productAttributes.options.metadata',
                'productAttributes.options.metadataPrivate',
            ]);
        }

        if (is_string($dto->price_sort_direction)) {
            if ($dto->price_sort_direction === 'price:asc') {
                $query->withMin([
                    'pricesMin as price' => fn (Builder $subquery) => $salesChannel ? $subquery->where('sales_channel_id', $salesChannel->id) : $subquery->where('currency', $dto->price_sort_currency ?? Currency::DEFAULT->value),
                ], 'value');
            }
            if ($dto->price_sort_direction === 'price:desc') {
                $query->withMax([
                    'pricesMin as price' => fn (Builder $subquery) => $salesChannel ? $subquery->where('sales_channel_id', $salesChannel->id) : $subquery->where('currency', $dto->price_sort_currency ?? Currency::DEFAULT->value),
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

        return $query->paginate(Config::get('pagination.per_page'));
    }
}
