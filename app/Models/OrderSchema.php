<?php

namespace App\Models;

use App\Traits\HasMetadata;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @mixin IdeHelperOrderSchema
 */
class OrderSchema extends Model
{
    use HasFactory, HasMetadata;

    protected $fillable = [
        'name',
        'value',
        'price',
        'order_product_id',
    ];
}
