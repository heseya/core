<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'external_id',
        'method',
        'status',
        'currency',
        'amount',
        'redirectUrl',
        'continueUrl',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
