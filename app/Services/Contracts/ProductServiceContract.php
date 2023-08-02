<?php

namespace App\Services\Contracts;

use App\Dtos\ProductCreateDto;
use App\Dtos\ProductSearchDto;
use App\Dtos\ProductUpdateDto;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProductServiceContract
{
    public function search(ProductSearchDto $dto): LengthAwarePaginator;

    public function create(ProductCreateDto $dto): Product;

    public function update(Product $product, ProductUpdateDto $dto): Product;

    public function delete(Product $product): void;

    /**
     * Returns minimum and maximum possible product price in
     * array formatted as such: [minimum, maximum].
     */
    public function getMinMaxPrices(Product $product): array;

    /**
     * Updates minimum and maximum possible product price.
     */
    public function updateMinMaxPrices(Product $product): void;
}
