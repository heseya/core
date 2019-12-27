<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'price',
        'description',
        'public',
        'tax_id',
        'brand_id',
        'category_id',
    ];

    protected $hidden = [
        'tax_id',
        'brand_id',
        'category_id',
        'created_at',
        'updated_at',
    ];

    public function gallery()
    {
        return $this->morphedByMany(Photo::class, 'media', 'product_gallery');
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class);
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
        return $this->hasMany(ProductSchema::class)->with('items');
    }
}
