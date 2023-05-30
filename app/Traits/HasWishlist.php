<?php

namespace App\Traits;

use App\Models\WishlistProduct;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasWishlist
{
    public function wishlistProducts(): MorphMany
    {
        return $this->morphMany(WishlistProduct::class, 'user');
    }

    public function wishlistProductsPublic(): MorphMany
    {
        return $this->morphMany(WishlistProduct::class, 'user')
            ->where('public', '=', true);
    }
}
