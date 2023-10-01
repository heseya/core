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
use Domain\Price\Enums\DiscountConditionPriceType;
use Domain\Price\Enums\OptionPriceType;
use Domain\Price\Enums\ProductPriceType;
use Domain\Price\Enums\SchemaPriceType;
use Domain\Price\PriceRepository;
use Domain\Product\Dtos\ProductSearchDto;
use Heseya\Dto\DtoException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use JetBrains\PhpStorm\NoReturn;
use Ramsey\Uuid\Uuid;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;
use Support\Dtos\ModelIdentityDto;

class ProductRepository implements ProductRepositoryContract
{
    use GetPublishedLanguageFilter;

    public function __construct(private PriceRepository $priceRepository) {}

    public function search(ProductSearchDto $dto): LengthAwarePaginator
    {
        $query = Product::searchByCriteria($dto->except('sort')->toArray() + $this->getPublishedLanguageFilter('products'))
            ->with(['attributes', 'metadata', 'media', 'publishedTags', 'items', 'pricesBase', 'pricesMin', 'pricesMax', 'pricesMinInitial', 'pricesMaxInitial']);

        if (Gate::denies('products.show_hidden')) {
            $query->where('products.public', true);
        }

        if (is_string($dto->price_sort_direction)) {
            if ($dto->price_sort_direction === 'price:asc') {
                $query->withMin([
                    'pricesMin as price' => fn (Builder $subquery) => $subquery->where('currency', $dto->price_sort_currency ?? Currency::DEFAULT->value),
                ], 'value');
            }
            if ($dto->price_sort_direction === 'price:desc') {
                $query->withMax([
                    'pricesMax as price' => fn (Builder $subquery) => $subquery->where('currency', $dto->price_sort_currency ?? Currency::DEFAULT->value),
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
        $this->priceRepository->setModelPrices(new ModelIdentityDto($productId, (new Product())->getMorphClass()), $priceMatrix);
    }

    /**
     * @param array<string, array<ProductPriceType, PriceDto[]>> $priceMatrix
     */
    public function setProductsPrices(array $priceMatrix): void
    {
        $rows = [];

        foreach ($priceMatrix as $productId => $typedPrices) {
            foreach ($typedPrices as $type => $prices) {
                foreach ($prices as $price) {
                    $rows[] = [
                        'id' => Uuid::uuid4(),
                        'model_id' => $productId,
                        'model_type' => (new Product())->getMorphClass(),
                        'price_type' => $type,
                        'currency' => $price->value->getCurrency()->getCurrencyCode(),
                        'value' => (string) $price->value->getMinorAmount(),
                        'is_net' => false,
                    ];
                }
            }
        }

        Price::query()->upsert(
            $rows,
            ['model_id', 'price_type', 'currency'],
            ['value', 'is_net'],
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
    public function getProductPrices(string $productId, array $priceTypes, ?Currency $currency = null): Collection|EloquentCollection
    {
        $prices = $this->priceRepository->getModelPrices(new ModelIdentityDto($productId, (new Product())->getMorphClass()), $priceTypes, $currency);

        $groupedPrices = $prices->mapToGroups(fn (Price $price) => [$price->price_type => PriceDto::from($price)]);

        foreach ($priceTypes as $type) {
            if (!$groupedPrices->has($type->value)) {
                throw new ServerException(Exceptions::SERVER_NO_PRICE_MATCHING_CRITERIA);
            }
        }

        return $groupedPrices;
    }

    /**
     * @param string[] $productIds
     * @param ProductPriceType[] $priceTypes
     *
     * @return array<string, array<ProductPriceType, PriceDto[]>>
     * @throws ServerException
     */
    public function getProductsPrices(array $productIds, array $priceTypes): array
    {
        $query = Price::query()
            ->whereIn('model_id', $productIds)
//            ->where('model_type', (new Product())->getMorphClass())
            ->whereIn('price_type', $priceTypes);

//        if ($currency !== null) {
//            $query->where('currency', $currency->value);
//        }

//        $groupedPrices = $query->get()->mapToGroups(fn (Price $price) => [$price->price_type => PriceDto::from($price)]);

        $priceMatrix = [];

        $prices = $query->get();

        foreach ($prices as $price) {
            $priceMatrix[$price->model_id][$price->price_type][] = PriceDto::from($price);
        }

        foreach ($priceMatrix as $typedPrices) {
            foreach ($priceTypes as $type) {
                if (!array_key_exists($type->value, $typedPrices)) {
                    throw new ServerException(Exceptions::SERVER_NO_PRICE_MATCHING_CRITERIA);
                }
            }
        }

        return $priceMatrix;
    }
}
