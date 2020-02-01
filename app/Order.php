<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'email',
        'client_id',
        'payment',
        'payment_status',
        'shop_status',
        'delivery',
        'delivery_status',
        'delivery_tracking',
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function summary()
    {
        $value = 0;

        foreach ($this->items as $item) {
            $value += $item->price * $item->qty;

            foreach ($item->descendants as $subItem) {
                $value += $subItem->price * $item->qty;
            }
        }

        return $value;
    }

    public function saveItems($items)
    {
        foreach ($items as $item) {
            $item = OrderItem::create($item);
            $this->items()->save($item);
        }
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
