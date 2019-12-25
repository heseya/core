<?php

namespace App;

use App\Category;
use App\ProductSchema;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = [
        'name',
        'symbol',
        'qty',
        'category_id',
    ];

    protected $hidden = [
        'category_id',
        'photo_id',
        'created_at',
        'updated_at',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function photo()
    {
        return $this->belongsTo(Photo::class);
    }

    public function schemas()
    {
        return $this->belongsToMany(ProductSchema::class, 'product_schema_item');
    }
}
