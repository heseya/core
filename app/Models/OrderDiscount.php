<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Relations\MorphPivot;

/**
 * @mixin IdeHelperOrderDiscount
 */
class OrderDiscount extends MorphPivot
{
    use HasUUID;

    protected $table = 'order_discounts';

    protected $fillable = [
        'type',
        'name',
        'code',
        'target_type',
        // /////
        'value',
        'applied_discount',
    ];

    protected $casts = [
        'value' => 'double',
        'applied_discount' => 'double',
    ];
}
