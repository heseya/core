<?php

namespace App;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Model;

class ProductSchemaItem extends Model
{
    protected $fillable = [
        'extra_price',
        'value',
        'item_id',
    ];

    public function schema()
    {
        return $this->belongsTo(ProductSchema::class, 'product_schema_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function orderItem()
    {
        return $this->belongsToMany(OrderItem::class);
    }
}
