<?php

namespace App\Models;

use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FavouriteProductSet extends Model
{
    use HasFactory,
        HasCriteria,
        SoftDeletes;

    protected $fillable = [
        'id',
        'user_id',
        'user_type',
        'product_set_id',
    ];

    public function productSet(): BelongsTo
    {
        return $this->belongsTo(ProductSet::class);
    }

    public function user(): MorphTo
    {
        return $this->morphTo('favouriteProductSets', 'user_type', 'user_id');
    }
}
