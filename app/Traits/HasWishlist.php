<?php

namespace App\Traits;

use App\Models\WishlistProduct;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasWishlist
{
    public function wishlistProducts(): MorphMany
    {
        return $this->morphMany(WishlistProduct::class, 'user')->with('product');
    }

    public function wishlistProductsPublic(): MorphMany
    {
        return $this->wishlistProducts()->whereHas(
            'product',
            /** @phpstan-ignore-next-line */
            fn (Builder $query) => $query->public(),
        );
    }
}
