<?php

namespace App\Models;

/**
 * @mixin IdeHelperOrderLog
 */
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
