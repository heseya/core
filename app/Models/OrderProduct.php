<?php

namespace App\Models;

use App\Traits\HasOrderDiscount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperOrderProduct
 */
class OrderProduct extends Model
{
    use HasFactory, HasOrderDiscount;

    protected $fillable = [
        'quantity',
        'base_price_initial',
        'base_price',
        'price_initial',
        'price',
        'order_id',
        'product_id',
        'name',
        'vat_rate',
        'shipping_digital',
        'is_delivered',
    ];

    protected $casts = [
        'vat_rate' => 'float',
        'shipping_digital' => 'boolean',
        'is_delivered' => 'boolean',
    ];

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

    public function urls(): HasMany
    {
        return $this->hasMany(OrderProductUrl::class);
    }
}
