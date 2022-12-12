<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderProductUrl extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
        'order_product_id',
    ];

    public function orderProduct(): BelongsTo
    {
        return $this->belongsTo(OrderProduct::class);
    }
}
