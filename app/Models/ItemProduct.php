<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ItemProduct extends Pivot
{
    protected $casts = [
        'quantity' => 'float'
    ];
}
