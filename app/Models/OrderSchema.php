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
//        'price_initial',
//        'price',
        'order_product_id',
    ];

    public function price(): MorphOneWithIdentifier
    {
        return $this->morphOneWithIdentifier(
            Price::class,
            'model',
            'price_type',
            'price',
        );
    }

    public function priceInitial(): MorphOneWithIdentifier
    {
        return $this->morphOneWithIdentifier(
            Price::class,
            'model',
            'price_type',
            'price_initial',
        );
    }
}
