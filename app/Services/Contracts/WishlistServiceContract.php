<?php

namespace App\Services\Contracts;

use App\Models\App;
use App\Models\Product;
use App\Models\User;
use App\Models\WishlistProduct;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface WishlistServiceContract
{
    public function index(User|App $user): LengthAwarePaginator;

    public function canView(User|App $user, Product $product): bool;

    public function storeWishlistProduct(User|App $user, string $id): WishlistProduct;

    public function destroy(User|App $user, Product $product): void;

    public function destroyAll(User|App $user): void;
}
