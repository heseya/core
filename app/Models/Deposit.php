<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperDeposit
 */
class Deposit extends Model
{
    use HasFactory;

    protected $fillable = [
        'quantity',
        'item_id',
        'order_product_id',
        'shipping_time',
        'shipping_date',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
