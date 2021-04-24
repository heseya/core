<?php

namespace App\Services\Contracts;

use App\Models\Product;

interface SchemaServiceContract
{
    public function sync(Product $product, array $schemas = []): void;
}
