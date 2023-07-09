<?php

namespace App\Services;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use App\Models\App;
use App\Models\Product;
use App\Models\User;
use App\Models\WishlistProduct;
use App\Services\Contracts\WishlistServiceContract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Config;

class WishlistService implements WishlistServiceContract
{
    public function index(App|User $user): LengthAwarePaginator
    {
        $query = $user->hasPermissionTo('products.show_hidden') ?
            $user->wishlistProducts() :
            $user->wishlistProductsPublic();

        return $query->paginate(Config::get('pagination.per_page'));
    }

    public function show(App|User $user, Product $product): WishlistProduct|null
    {
        $query = $user->hasPermissionTo('products.show_hidden') ?
            $user->wishlistProducts() :
            $user->wishlistProductsPublic();

        // weird firstOr because laravel typing -_-
        return $query->where('product_id', $product->getKey())->firstOr(fn () => null);
    }

    public function storeWishlistProduct(App|User $user, string $id): WishlistProduct
    {
        return $user->wishlistProducts()->create([
            'product_id' => $id,
        ]);
    }

    /**
     * @throws ClientException
     */
    public function destroy(App|User $user, Product $product): void
    {
        if (!$user->wishlistProducts()->where('product_id', $product->getKey())->delete()) {
            throw new ClientException(Exceptions::PRODUCT_IS_NOT_ON_WISHLIST);
        }
    }

    public function destroyAll(App|User $user): void
    {
        $user->wishlistProducts()->delete();
    }
}
