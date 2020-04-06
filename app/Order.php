<?php

namespace App;

use App\Payment;
use App\Payment\Payable;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use Payable;

    protected $fillable = [
        'code',
        'email',
        'client_id',
        'payment_status',
        'shop_status',
        'delivery_method',
        'delivery_status',
        'delivery_tracking',
        'comment',
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

    public function canChangePaymentStatus(): boolean
    {
        if ($this->payment_method === null) {
            return true;
        }

        return false;
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function deliveryAddress()
    {
        return $this->hasOne(Address::class, 'id', 'delivery_address');
    }

    public function invoiceAddress()
    {
        return $this->hasOne(Address::class, 'id', 'invoice_address');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function logs()
    {
        return $this->hasMany(OrderLog::class)->orderBy('created_at', 'DESC');
    }

    public function notes()
    {
        return $this->hasMany(OrderNote::class)->orderBy('created_at', 'DESC');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class)->using(OrderItem::class);
    }
}
