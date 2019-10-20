<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderLog extends Model
{
    public $timestamps = ['created_at'];

    protected $fillable = [
        'order_id',
        'content',
        'user',
        'created_at',
    ];
}
