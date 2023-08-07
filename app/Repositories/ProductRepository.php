<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Dtos\PriceDto;
use App\Dtos\ProductSearchDto;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\Product\ProductPriceType;
use App\Exceptions\ServerException;
use App\Models\Price;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryContract;
use Domain\Currency\Currency;
use Heseya\Dto\DtoException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Ramsey\Uuid\Uuid;

class ProductRepository implements ProductRepositoryContract
{
    public function search(ProductSearchDto $dto): LengthAwarePaginator
    {
        $query = Product::searchByCriteria($dto->toArray())
            ->with(['attributes', 'metadata', 'media', 'tags', 'items', 'pricesBase', 'pricesMin', 'pricesMax', 'pricesMinInitial', 'pricesMaxInitial'])
            ->sort($dto->getSort());

        if (Gate::denies('products.show_hidden')) {
            $query->where('products.public', true);
        }

        return $query->paginate(Config::get('pagination.per_page'));
    }

    /**
     * @param PriceDto[][] $priceMatrix
     */
    public static function setProductPrices(string $productId, array $priceMatrix): void
    {
        $rows = [];

        foreach ($priceMatrix as $type => $prices) {
            foreach ($prices as $price) {
                $rows[] = [
                    'id' => Uuid::uuid4(),
                    'model_id' => $productId,
                    'model_type' => Product::class,
                    'price_type' => $type,
                    'currency' => $price->value->getCurrency()->getCurrencyCode(),
                    'value' => $price->value->getMinorAmount(),
                    'is_net' => false,
                ];
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
     * @return PriceDto[][]
     *
     * @throws DtoException
     * @throws ServerException
     */
    public static function getProductPrices(string $productId, array $priceTypes, ?Currency $currency = null): array
    {
        $prices = Price::query()
            ->where('model_id', $productId)
            ->whereIn('price_type', $priceTypes);

        if ($currency !== null) {
            $prices = $prices->where('currency', $currency->value);
        }

        $groupedPrices = $prices->get()->reduce(function (array $carry, Price $price) {
            $carry[$price->price_type][] = new PriceDto($price->value);

            return $carry;
        }, []);

        return array_map(
            function (ProductPriceType $type) use ($groupedPrices) {
                if (!array_key_exists($type->value, $groupedPrices)) {
                    throw new ServerException(Exceptions::SERVER_NO_PRICE_MATCHING_CRITERIA);
                }

                return $groupedPrices[$type->value];
            },
            $priceTypes,
        );
    }
}
