<?php

namespace App\Models;

use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperWishlistProduct
 */
class WishlistProduct extends Model
{
    use HasFactory,
        HasCriteria,
        SoftDeletes;

    protected $fillable = [
        'user_id',
        'user_type',
        'product_id',
    ];

    public function user(): MorphTo
    {
        return $this->morphTo('order', 'buyer_type', 'buyer_id', 'id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
