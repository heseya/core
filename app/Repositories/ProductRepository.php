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
use Domain\Product\Enums\ProductSalesChannelStatus;
use Domain\Product\Models\ProductSalesChannel;
use Domain\SalesChannel\Models\SalesChannel;
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

    public function __construct(private PriceRepository $priceRepository) {}

    public function search(ProductSearchDto $dto): LengthAwarePaginator
    {
        $query = Product::searchByCriteria($dto->except('sort', 'public', 'all')->toArray() + $this->getPublishedLanguageFilter('products'))
            ->with(['attributes', 'metadata', 'media', 'publishedTags', 'items', 'pricesBase', 'pricesMin', 'pricesMax', 'pricesMinInitial', 'pricesMaxInitial']);

        $salesChannel = Config::get('sales-channel.model');

        if (Gate::denies('products.show_hidden')) {
            $query->whereHas('salesChannels', fn (Builder $subquery) => $subquery
                ->where('sales_channel_id', $salesChannel->getKey())
                ->where(app(ProductSalesChannel::class)->qualifyColumn('availability_status'), ProductSalesChannelStatus::PUBLIC->value));
        } elseif (!$dto->all) {
            $query->whereHas('salesChannels', function (Builder $subquery) use ($salesChannel, $dto) {
                $subquery->where('sales_channel_id', $salesChannel->getKey());
                if ($dto->public) {
                    $subquery->where(app(ProductSalesChannel::class)->qualifyColumn('availability_status'), '=', ProductSalesChannelStatus::PUBLIC->value);
                } else {
                    $subquery->where(app(ProductSalesChannel::class)->qualifyColumn('availability_status'), '!=', ProductSalesChannelStatus::DISABLED->value);
                }

                return $subquery;
            });
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
    public function setProductPrices(Product|string $product, array $priceMatrix): void
    {
        $this->priceRepository->setModelPrices(is_string($product) ? new ModelIdentityDto($product, Product::class) : $product, $priceMatrix);
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
        Product|string $product,
        array $priceTypes,
        ?Currency $currency = null,
        ?SalesChannel $salesChannel = null,
    ): Collection|EloquentCollection {
        $prices = $this->priceRepository->getModelPrices(is_string($product) ? new ModelIdentityDto($product, Product::class) : $product, $priceTypes, $currency, $salesChannel);

        $groupedPrices = $prices->mapToGroups(fn (Price $price) => [$price->price_type => PriceDto::from($price)]);

        foreach ($priceTypes as $type) {
            if (!$groupedPrices->has($type->value)) {
                throw new ServerException(Exceptions::SERVER_NO_PRICE_MATCHING_CRITERIA);
            }
        }

        return $groupedPrices;
    }
}
