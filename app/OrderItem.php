<?php

namespace App;

use Kalnoy\Nestedset\NodeTrait;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use NodeTrait;

    protected $fillable = [
        'symbol',
        'name',
        'qty',
        'price',
    ];
}
