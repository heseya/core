<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @mixin IdeHelperOrderSchema
 */
class OrderSchema extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'value',
        'price_initial',
        'price',
        'order_product_id',
    ];
}
