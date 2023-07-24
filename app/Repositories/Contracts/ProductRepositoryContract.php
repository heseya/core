<?php

namespace App\Repositories\Contracts;

use App\Dtos\PriceDto;
use App\Dtos\ProductSearchDto;
use App\Enums\Product\ProductPriceType;
use Heseya\Dto\DtoException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProductRepositoryContract
{
    public function search(ProductSearchDto $dto): LengthAwarePaginator;

    /**
     * @param array<string, PriceDto[]> $priceMatrix
     */
    public static function setProductPrices(string $productId, array $priceMatrix): void;

    /**
     * @param string $productId
     * @param ProductPriceType[] $priceTypes
     *
     * @return PriceDto[][]
     * @throws DtoException
     */
    public static function getProductPrices(string $productId, array $priceTypes): array;
}
