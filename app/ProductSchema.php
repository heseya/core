<?php

namespace App;

use App\Item;
use App\Product;
use Illuminate\Database\Eloquent\Model;

class ProductSchema extends Model
{
    protected $fillable = [
        'name',
    ];

    protected $hidden = [
        'product_id',
        'created_at',
        'updated_at',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function items()
    {
        return $this->belongsToMany(Item::class, 'product_schema_item')
            ->withPivot('extra_price')
            ->orderBy('symbol');
    }
}
