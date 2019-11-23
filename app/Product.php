<?php

namespace App;

use App\Brand;
use App\Category;
use App\ProductSchema;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'price',
        'description',
        'public',
        'brand_id',
        'category_id',
    ];

    protected $hidden = [
        'brand_id',
        'category_id',
        'created_at',
        'updated_at',
    ];

    public function gallery()
    {
        return $this->morphedByMany(Photo::class, 'media', 'product_gallery');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function shema()
    {
        return $this->hasMany(ProductSchema::class);
    }
}
