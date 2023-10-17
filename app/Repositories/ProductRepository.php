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

        if (request()->isNotFilled('attribute_slug')) {
            $query->with(
                [
                    'productAttributes',
                    'productAttributes.options',
                    'productAttributes.options.metadata',
                    'productAttributes.options.metadataPrivate',
                    'productAttributes.attribute',
                    'productAttributes.attribute.options',
                    'productAttributes.attribute.options.metadata',
                    'productAttributes.attribute.options.metadataPrivate',
                ],
            );
        }

        if (!$dto->full instanceof Optional && $dto->full) {
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
                'sales',
                'sales.metadata',
                'sales.metadataPrivate',
                'sales.amounts',
                'sales.orders',
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

            if (request()->isNotFilled('attribute_slug')) {
                $query->with(
                    [
                        'productAttributes.attribute.metadata',
                        'productAttributes.attribute.metadataPrivate',
                    ],
                );
            }
        }

        if (Gate::denies('products.show_hidden')) {
            $query->where('products.public', true);
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
