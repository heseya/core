<?php

namespace App\Repositories\Contracts;

use App\Dtos\PriceDto;
use App\Dtos\PriceModelDto;
use App\Enums\Product\ProductPriceType;
use App\Exceptions\ServerException;
use Domain\Currency\Currency;
use Domain\Product\ProductSearchDto;
use Heseya\Dto\DtoException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** @see \App\Repositories\ProductRepository */
interface ProductRepositoryContract
{
    public function search(ProductSearchDto $dto): LengthAwarePaginator;

    /**
     * @param array<string, PriceDto[]> $priceMatrix
     */
    public static function setProductPrices(string $productId, array $priceMatrix): void;

    /**
     * @param PriceModelDto[] $prices
     */
    public static function setProductsPrices(array $prices): void;

    /**
     * @param ProductPriceType[] $priceTypes
     *
     * @return PriceDto[][]
     *
     * @throws DtoException
     */
    public static function getProductPrices(string $productId, array $priceTypes, ?Currency $currency = null): array;

    /**
     * @param ProductPriceType[] $priceTypes
     *
     * @return PriceModelDto[][]
     *
     * @throws DtoException
     * @throws ServerException
     */
    public static function getProductsPrices(array $productIds, array $priceTypes, ?Currency $currency = null): array;
}
