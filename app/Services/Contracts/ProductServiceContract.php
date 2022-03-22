<?php

namespace App\Services\Contracts;

use App\Models\Product;

interface ProductServiceContract
{
    public function assignItems(Product $product, array|null $itemsIds): Product;
    /**
     * Returns minimum and maximum possible product price in
     * array formatted as such: [minimum, maximum]
     *
     * @param Product $product
     *
     * @return array
     */
    public function getMinMaxPrices(Product $product): array;

    /**
     * Updates minimum and maximum possible product price
     *
     * @param Product $product
     */
    public function updateMinMaxPrices(Product $product);
}
