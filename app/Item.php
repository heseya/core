<?php

namespace App;

use App\Category;
use App\ProductSchema;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use HasTranslations, SoftDeletes;

    public $translatable = [
        'name',
    ];

    protected $fillable = [
        'name',
        'symbol',
        'qty',
        'category_id',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function photo()
    {
        return $this->belongsTo(Photo::class);
    }

    public function schemaItems()
    {
        return $this->hasMany(ProductSchemaItem::class);
    }
}
