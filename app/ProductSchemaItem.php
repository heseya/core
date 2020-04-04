<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductSchemaItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'extra_price',
        'value',
        'item_id',
    ];

    public function schema()
    {
        return $this->belongsTo(ProductSchema::class, 'product_schema_id')->withTrashed();
    }

    public function item()
    {
        return $this->belongsTo(Item::class)->withTrashed();
    }

    public function orderItem()
    {
        return $this->belongsToMany(OrderItem::class);
    }
}
