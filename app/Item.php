<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = [
        'name',
        'symbol',
        'qty',
        'photo',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
