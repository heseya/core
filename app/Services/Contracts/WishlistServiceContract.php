<?php

namespace App\Services\Contracts;

use App\Models\App;
use App\Models\Product;
use App\Models\User;
use App\Models\WishlistProduct;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface WishlistServiceContract
{
    public function index(App|User $user): LengthAwarePaginator;

    public function show(App|User $user, Product $product): WishlistProduct|null;

    public function storeWishlistProduct(App|User $user, string $id): WishlistProduct;

    public function destroy(App|User $user, Product $product): void;

    public function destroyAll(App|User $user): void;
}
