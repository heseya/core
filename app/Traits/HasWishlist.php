<?php

namespace App\Traits;

use App\Models\WishlistProduct;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasWishlist
{
    /**
     * @return MorphMany<WishlistProduct>
     */
    public function wishlistProducts(): MorphMany
    {
        return $this->morphMany(WishlistProduct::class, 'user')->with('product');
    }

    /**
     * @return MorphMany<WishlistProduct>
     */
    public function wishlistProductsPublic(): MorphMany
    {
        return $this->wishlistProducts()->whereHas(
            'product',
            fn (Builder $query) => $query->where('public', '=', true),
        );
    }
}
