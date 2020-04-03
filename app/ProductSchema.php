<?php

namespace App;

use App\Item;
use App\Product;
use App\ProductSchemaItem;
use Illuminate\Database\Eloquent\Model;

class ProductSchema extends Model
{
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
