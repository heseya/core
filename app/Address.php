<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = [
        'name',
        'address',
        'nip',
        'zip',
        'city',
        'country',
        'phone',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
