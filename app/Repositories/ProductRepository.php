<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ServerException;
use App\Models\Price;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryContract;
use App\Traits\GetPublishedLanguageFilter;
use Domain\Currency\Currency;
use Domain\Price\Dtos\PriceDto;
use Domain\Price\Enums\ProductPriceType;
use Domain\Price\PriceRepository;
use Domain\Product\Dtos\ProductSearchDto;
use Heseya\Dto\DtoException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelData\Optional;
use Support\Dtos\ModelIdentityDto;

class ProductRepository implements ProductRepositoryContract
{
    use GetPublishedLanguageFilter;

    public function __construct(private readonly PriceRepository $priceRepository) {}

    public function search(ProductSearchDto $dto): LengthAwarePaginator
    {
        if (Config::get('search.use_scout') && is_string($dto->search)) {
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
                'pricesBase',
                'pricesMin',
                'pricesMax',
                'pricesMinInitial',
                'pricesMaxInitial',
                'metadata',
                'metadataPrivate',
            ]);

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
                'schemas.prices',
                'schemas.metadata',
                'schemas.metadataPrivate',
                'schemas.usedSchemas',
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
        }

        if (Gate::denies('products.show_hidden')) {
            $query->where('products.public', true);
        }

        $loadAttributes = collect();
        if (is_array($dto->attribute)) {
            $loadAttributes->push(...array_keys($dto->attribute));
        }
        if (request()->filled('attribute_slug')) {
            $loadAttributes->push(request()->string('attribute_slug'));
        }
        if ($loadAttributes->isNotEmpty()) {
            $query->with(['productAttributes' => fn (Builder|HasMany $subquery) => $subquery->slug($loadAttributes->toArray())]); // @phpstan-ignore-line
        }

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
        if (!$dto->sort instanceof Optional) {
            $query->sort($dto->sort);
        }

        return $query->paginate(Config::get('pagination.per_page'));
    }

    /**
     * @param PriceDto[][] $priceMatrix
     */
    public function setProductPrices(string $productId, array $priceMatrix): void
    {
        $this->priceRepository->setModelPrices(
            new ModelIdentityDto($productId, (new Product())->getMorphClass()),
            $priceMatrix,
        );
    }

    /**
     * @param ProductPriceType[] $priceTypes
     *
     * @return Collection|EloquentCollection<string,Collection<int,PriceDto>|EloquentCollection<int,PriceDto>>
     *
     * @throws DtoException
     * @throws ServerException
     */
    public function getProductPrices(
        string $productId,
        array $priceTypes,
        ?Currency $currency = null,
    ): Collection|EloquentCollection {
        $prices = $this->priceRepository->getModelPrices(
            new ModelIdentityDto($productId, (new Product())->getMorphClass()),
            $priceTypes,
            $currency,
        );

        $groupedPrices = $prices->mapToGroups(fn (Price $price) => [$price->price_type => PriceDto::from($price)]);

        foreach ($priceTypes as $type) {
            if (!$groupedPrices->has($type->value)) {
                throw new ServerException(Exceptions::SERVER_NO_PRICE_MATCHING_CRITERIA);
            }
        }

        return $groupedPrices;
    }
}
