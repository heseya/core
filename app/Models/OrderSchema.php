<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Domain\Currency\Currency;
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
        'order_product_id',

        'currency',
        'price_initial',
        'price',
    ];

    protected $casts = [
        'currency' => Currency::class,
        'price_initial' => MoneyCast::class,
        'price' => MoneyCast::class,
    ];
}
