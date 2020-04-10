<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'price',
        'description',
        'digital',
        'public',
        'brand_id',
        'category_id',
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

    public function schemas()
    {
        return $this->hasMany(ProductSchema::class);
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class)->using(OrderItem::class);
    }

    /**
     * MD description parser.
     *
     * @var array
     */
    public function getParsedDescriptionAttribute(): string
    {
        return parsedown($this->description);
    }
}
