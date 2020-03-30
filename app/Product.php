<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    use HasTranslations;

    public $translatable = [
        'name',
        'description',
    ];

    protected $fillable = [
        'name',
        'slug',
        'price',
        'description',
        'digital',
        'public',
        'tax_id',
        'brand_id',
        'category_id',
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

    public function schemas()
    {
        return $this->hasMany(ProductSchema::class)->with('items');
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
