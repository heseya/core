<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductItem extends Pivot
{
    protected $casts = [
        'quantity' => 'float',
    ];
}
