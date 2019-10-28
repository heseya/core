<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name',
        'link',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
