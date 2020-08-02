<?php

namespace App\Models;

class OrderNote extends Model
{
    protected $fillable = [
        'message',
        'user_id',
        'order_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
