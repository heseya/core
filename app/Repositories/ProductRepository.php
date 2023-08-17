<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\Product\ProductPriceType;
use App\Exceptions\ServerException;
use App\Models\Price;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryContract;
use App\Traits\GetPublishedLanguageFilter;
use Domain\Currency\Currency;
use Domain\Price\Dtos\PriceDto;
use Domain\Price\PriceRepository;
use Domain\Product\Dtos\ProductSearchDto;
use Heseya\Dto\DtoException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Support\Dtos\ModelIdentityDto;

class ProductRepository implements ProductRepositoryContract
{
    use GetPublishedLanguageFilter;

    public function __construct(private PriceRepository $priceRepository) {}

    public function search(ProductSearchDto $dto): LengthAwarePaginator
    {
        $query = Product::searchByCriteria($dto->except('sort')->toArray() + $this->getPublishedLanguageFilter('products'))
            ->with(['attributes', 'metadata', 'media', 'tags', 'items', 'pricesBase', 'pricesMin', 'pricesMax', 'pricesMinInitial', 'pricesMaxInitial']);

        if (Gate::denies('products.show_hidden')) {
            $query->where('products.public', true);
        }

        if (is_string($dto->sort)) {
            if (Str::contains($dto->sort, 'price_min')) {
                $query->withMin([
                    'pricesMin as price_min' => fn (Builder $subquery) => $subquery->where('currency', $dto->getCurrency()->getCurrencyCode()),
                ], 'value');
            }
            if (Str::contains($dto->sort, 'price_max')) {
                $query->withMax([
                    'pricesMax as price_max' => fn (Builder $subquery) => $subquery->where('currency', $dto->getCurrency()->getCurrencyCode()),
                ], 'value');
            }
            $query->sort($dto->sort);
        } else {
            $query->sort();
        }

        return $query->paginate(Config::get('pagination.per_page'));
    }

    /**
     * @param PriceDto[][] $priceMatrix
     */
    public function setProductPrices(string $productId, array $priceMatrix): void
    {
        $this->priceRepository->setModelPrices(new ModelIdentityDto($productId, Product::class), $priceMatrix);
    }

    /**
     * @param ProductPriceType[] $priceTypes
     *
     * @return Collection|EloquentCollection<string,Collection<int,PriceDto>|EloquentCollection<int,PriceDto>>
     *
     * @throws DtoException
     * @throws ServerException
     */
    public function getProductPrices(string $productId, array $priceTypes, ?Currency $currency = null): Collection|EloquentCollection
    {
        $prices = $this->priceRepository->getModelPrices(new ModelIdentityDto($productId, Product::class), $priceTypes, $currency);

        $groupedPrices = $prices->mapToGroups(fn (Price $price) => [$price->price_type->value => PriceDto::from($price)]);

        foreach ($priceTypes as $type) {
            if (!$groupedPrices->has($type->value)) {
                throw new ServerException(Exceptions::SERVER_NO_PRICE_MATCHING_CRITERIA);
            }
        }

        return $groupedPrices;
    }
}
