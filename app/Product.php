<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'category_id',
        'brand_id',
    ];

    public function brand()
    {
        return $this->hasOne(Product::class);
    }

    public function category()
    {
        return $this->hasOne(Product::class);
    }
}
