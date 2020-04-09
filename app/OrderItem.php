<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'qty',
        'price',
        'tax',
        'order_id',
        'product_id',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function schemaItems()
    {
        return $this->belongsToMany(ProductSchemaItem::class)->withTrashed();
    }
}
