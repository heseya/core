<?php

namespace App;

use App\Item;
use App\Product;
use App\ProductSchemaItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductSchema extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'required',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function schemaItems()
    {
        return $this->hasMany(ProductSchemaItem::class)->with('item');
    }
}
