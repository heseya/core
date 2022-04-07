<?php

namespace App\Services\Contracts;

use App\Models\Product;

interface ProductSearchServiceContract
{
    public function mapSearchableArray(Product $product): array;
}