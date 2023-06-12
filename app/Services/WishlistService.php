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
    public function index(User|App $user): LengthAwarePaginator
    {
        $query = $user->wishlistProducts();

        if (!$user->hasPermissionTo('products.show_hidden')) {
            $query->where('public', '=', true);
        }

        return $query->paginate(Config::get('pagination.per_page'));
    }

    public function canView(User|App $user, Product $product): bool
    {
        $query = $user->wishlistProducts();

        if (!$user->hasPermissionTo('products.show_hidden')) {
            $query->where('public', '=', true);
        }

        return $query
            ->where('product_id', $product->getKey())
            ->exists();
    }

    public function storeWishlistProduct(User|App $user, string $id): WishlistProduct
    {
        return $user->wishlistProducts()->create([
            'product_id' => $id,
        ]);
    }

    /**
     * @throws ClientException
     */
    public function destroy(User|App $user, Product $product): void
    {
        if (!$user->wishlistProducts()->where('product_id', $product->getKey())->delete()) {
            throw new ClientException(Exceptions::PRODUCT_IS_NOT_ON_WISHLIST);
        }
    }

    public function destroyAll(User|App $user): void
    {
        $user->wishlistProducts()->delete();
    }
}
