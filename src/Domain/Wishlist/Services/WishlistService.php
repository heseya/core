<?php

declare(strict_types=1);

namespace Domain\Wishlist\Services;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use App\Models\App;
use App\Models\Product;
use App\Models\User;
use App\Models\WishlistProduct;
use Domain\Wishlist\Dtos\WishlistCheckDto;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

final class WishlistService
{
    /**
     * @return LengthAwarePaginator<WishlistProduct>
     */
    public function index(App|User $user): LengthAwarePaginator
    {
        $query = $user->hasPermissionTo('products.show_hidden') ?
            $user->wishlistProducts() :
            $user->wishlistProductsPublic();

        return $query->paginate(Config::get('pagination.per_page'));
    }

    public function show(App|User $user, Product $product): ?WishlistProduct
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

    /**
     * @return Collection<int, string>
     */
    public function check(App|User $user, WishlistCheckDto $dto): Collection
    {
        $query = $user->hasPermissionTo('products.show_hidden') ?
            $user->wishlistProducts() :
            $user->wishlistProductsPublic();

        return $query->whereIn('product_id', $dto->product_ids)->pluck('product_id');
    }
}
