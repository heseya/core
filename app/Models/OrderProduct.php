<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperOrderProduct
 */
class OrderProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'quantity',
        'price',
        'order_id',
        'product_id',
    ];

    public function getPriceAttribute($value): float
    {
        return $value + $this->schemas()->sum('price');
    }

    public function schemas(): HasMany
    {
        return $this->hasMany(OrderSchema::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }
}
