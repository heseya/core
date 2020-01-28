<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
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

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'tax_id',
        'brand_id',
        'category_id',
        'created_at',
        'updated_at',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

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
