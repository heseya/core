<?php

namespace App\Services\Contracts;

use App\Models\Product;

interface MediaServiceContract
{
    public function sync(Product $product, array $media): void;
}
