<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    protected $fillable = [
        'name',
        'value',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
