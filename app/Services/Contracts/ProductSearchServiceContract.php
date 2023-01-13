<?php

namespace App\Services\Contracts;

use App\Models\Product;

interface ProductSearchServiceContract
{
    public function mapSearchableArray(Product $product): array;

    public function mappableAs(): array;

    public function searchableFields(): array;
}
