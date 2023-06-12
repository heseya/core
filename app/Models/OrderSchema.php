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
        'order_product_id',
        // ////////////
        'price_initial',
        'price',
    ];

    //    public function price(): MorphOneWithIdentifier
    //    {
    //        return $this->morphOneWithIdentifier(
    //            Price::class,
    //            'model',
    //            'price_type',
    //            'price',
    //        );
    //    }
    //
    //    public function priceInitial(): MorphOneWithIdentifier
    //    {
    //        return $this->morphOneWithIdentifier(
    //            Price::class,
    //            'model',
    //            'price_type',
    //            'price_initial',
    //        );
    //    }
}
