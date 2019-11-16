<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'public',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
