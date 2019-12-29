<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'code',
        'payment',
        'payment_status',
        'shop_status',
        'delivery',
        'delivery_status',
    ];

    public function summary()
    {
        $value = 0;

        foreach ($this->items as $item) {
            $value += $item->price;

            foreach ($item->descendants as $subItem) {
                $value += $subItem->price;
            }
        }

        return $value;
    }

    public function items()
    {
        return $this->belongsToMany(OrderItem::class, 'order_order_item');
    }

    public function deliveryAddress()
    {
        return $this->hasOne(Address::class, 'id', 'delivery_address');
    }

    public function invoiceAddress()
    {
        return $this->hasOne(Address::class, 'id', 'invoice_address');
    }

    public function logs()
    {
        return $this->hasMany(OrderLog::class)->orderBy('created_at', 'DESC');
    }
}
