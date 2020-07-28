<?php

namespace App\Models;

class OrderLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'content',
        'user',
        'created_at',
    ];
}
