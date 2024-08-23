<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ServerException;
use App\Models\Price;
use App\Models\Product;
use App\Traits\GetPublishedLanguageFilter;
use Domain\Currency\Currency;
use Domain\Price\Dtos\ProductCachedPriceDto;
use Domain\Price\Dtos\ProductCachedPricesDto;
use Domain\Price\Dtos\ProductCachedPricesDtoCollection;
use Domain\Price\Enums\PriceTypeValues;
use Domain\Price\Enums\ProductPriceType;
use Domain\Price\PriceRepository;
use Domain\PriceMap\PriceMap;
use Domain\PriceMap\PriceMapService;
use Domain\Product\Dtos\ProductSearchDto;
use Domain\SalesChannel\Models\SalesChannel;
use Domain\SalesChannel\SalesChannelService;
use Heseya\Dto\DtoException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelData\Optional;
use Support\Dtos\ModelIdentityDto;

class ProductRepository
{
    use GetPublishedLanguageFilter;

    public function __construct(
        private readonly PriceRepository $priceRepository,
        private readonly SalesChannelService $salesChannelService,
        private readonly PriceMapService $priceMapService,
    ) {}

    public function search(ProductSearchDto $dto): LengthAwarePaginator
    {
        if (Config::get('search.use_scout') && is_string($dto->search) && !empty($dto->search)) {
            $scoutResults = Product::search($dto->search)->keys()->toArray();
            $dto->search = new Optional();
            $dto->ids = is_array($dto->ids) && !empty($dto->ids)
                ? array_intersect($scoutResults, $dto->ids)
                : $scoutResults;
        }

        $salesChannel = $this->salesChannelService->getCurrentRequestSalesChannel();
        $priceMap = $salesChannel->priceMap ?? PriceMap::findOrFail($dto->getCurrency()->getDefaultPriceMapId());

        assert($priceMap instanceof PriceMap);

        /** @var Builder<Product> $query */
        $query = Product::searchByCriteria($dto->except('sort')->toArray() + $this->getPublishedLanguageFilter('products'))
            ->with([
                'media',
                'media.metadata',
                'media.metadataPrivate',
                'publishedTags',
                'pricesBase',
                'pricesMin',
                'pricesMax',
                'pricesMinInitial',
                'pricesMaxInitial',
                'metadata',
                'metadataPrivate',
            ]);
        $query->with(['mapPrices' => fn(Builder|HasMany $hasMany) => $hasMany->where('price_map_id', $priceMap->id)]);

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
            $query->with(['sales' => fn(BelongsToMany|Builder $hasMany) => $hasMany->withOrdersCount()]); // @phpstan-ignore-line
            $query->with(['schemas.options.mapPrices' => fn(Builder|HasMany $hasMany) => $hasMany->where('price_map_id', $priceMap->id)]);
        }

        if (Gate::denies('products.show_hidden')) {
            $query->where('products.public', true);
        }

        if (request()->filled('attribute_slug')) {
            $query->with([
                'productAttributes' => fn(Builder|HasMany $subquery) => $subquery->slug(explode(';', request()->input('attribute_slug'))), // @phpstan-ignore-line
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
                    'pricesMin as price' => fn(Builder $subquery) => $subquery->where(
                        'currency',
                        $dto->price_sort_currency ?? Currency::DEFAULT->value,
                    ),
                ], 'value');
            }
            if ($dto->price_sort_direction === 'price:desc') {
                $query->withMax([
                    'pricesMax as price' => fn(Builder $subquery) => $subquery->where(
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

        return $query->paginate(Config::get('pagination.per_page'));
    }

    /**
     * @deprecated
     */
    public function setProductPrices(Product|string $product, array $priceMatrix): void
    {
        if (array_key_exists(ProductPriceType::PRICE_BASE->value, $priceMatrix)) {
            $this->priceMapService->updateProductPricesForDefaultMaps($product, $priceMatrix[ProductPriceType::PRICE_BASE->value]);
        } elseif (array_key_exists(ProductPriceType::PRICE_MIN_INITIAL->value, $priceMatrix)) {
            $this->priceMapService->updateProductPricesForDefaultMaps($product, $priceMatrix[ProductPriceType::PRICE_MIN_INITIAL->value]);
        } elseif (array_key_exists(ProductPriceType::PRICE_MIN->value, $priceMatrix)) {
            $this->priceMapService->updateProductPricesForDefaultMaps($product, $priceMatrix[ProductPriceType::PRICE_MIN->value]);
        }
    }

    /**
     * @param ProductCachedPricesDtoCollection|ProductCachedPricesDto[]|array<value-of<ProductPriceType>,ProductCachedPriceDto[]> $priceMatrix
     */
    public function setCachedProductPrices(Product|string $product, array|ProductCachedPricesDtoCollection $priceMatrix): void
    {
        $this->priceRepository->setCachedProductPrices(
            $product instanceof Product ? $product : new ModelIdentityDto($product, (new Product())->getMorphClass()),
            $priceMatrix,
        );
    }

    /**
     * @param ProductPriceType[] $priceTypes
     *
     * @return Collection<string,Collection<int,ProductCachedPriceDto>>
     *
     * @throws DtoException
     * @throws ServerException
     */
    public function getCachedProductPrices(
        Product|string $product,
        array $priceTypes,
        Currency|SalesChannel|null $filter = null,
    ): Collection {
        $prices = $this->priceRepository->getModelPrices(
            $product instanceof Product ? $product : new ModelIdentityDto($product, (new Product())->getMorphClass()),
            $priceTypes,
            $filter,
        );

        $groupedPrices = $prices->collect()->mapToGroups(fn(Price $price) => [$price->price_type => ProductCachedPriceDto::from($price)]);

        foreach ($priceTypes as $type) {
            if (!$groupedPrices->has($type->value)) {
                throw new ServerException(Exceptions::SERVER_NO_PRICE_MATCHING_CRITERIA);
            }
        }

        return $groupedPrices;
    }
}
