<?php

namespace App\Services\Contracts;

use App\Models\Product;
use App\Models\WishlistProduct;

interface WishlistServiceContract
{
    public function storeWishlistProduct(string $id): WishlistProduct;
    public function destroy(Product $product): void;
    public function destroyAll(): void;
}
