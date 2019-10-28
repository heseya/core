<?php

namespace App;

use App\Brand;
use App\Category;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'price',
        'color',
        'description',
        'brand_id',
        'category_id',
    ];

    protected $hidden = [
        'brand_id',
        'category_id',
        'created_at',
        'updated_at',
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
