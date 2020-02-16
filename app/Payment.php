<?php

namespace App;

use App\Order;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'external_id',
        'method',
        'status',
        'currency',
        'amount',
        'url',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
