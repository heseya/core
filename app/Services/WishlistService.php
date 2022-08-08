<?php

namespace App\Services;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use App\Models\Product;
use App\Models\WishlistProduct;
use App\Services\Contracts\WishlistServiceContract;
use Illuminate\Support\Facades\Auth;

class WishlistService implements WishlistServiceContract
{
    public function storeWishlistProduct(string $id): WishlistProduct
    {
        return WishlistProduct::create([
            'product_id' => $id,
            'user_id' => Auth::id(),
            'user_type' => Auth::user()::class,
        ]);
    }

    /**
     * @throws ClientException
     */
    public function destroy(Product $product): void
    {
        $wishlistProduct = Auth::user()->wishlistProducts()->where('product_id', $product->getKey());

        if ($wishlistProduct->first() === null) {
            throw new ClientException(Exceptions::PRODUCT_IS_NOT_ON_WISHLIST);
        }

        $wishlistProduct->delete();
    }
}
