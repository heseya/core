<?php

namespace App\Services\Contracts;

use App\Dtos\ProductPriceDto;

interface PriceServiceContract
{
    /**
     * @return ProductPriceDto[]
     */
    public function calcProductsListDiscounts(array $productIds): array;
}
