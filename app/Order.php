<?php

namespace App;

use App\Payment;
use App\Payment\Payable;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema()
 */
class Order extends Model
{
    use Payable;

    /**
     * @OA\Property(
     *   property="id",
     *   type="integer",
     * )
     *
     * @OA\Property(
     *   property="email",
     *   type="string",
     *   example="admin@example.com",
     * )
     *
     * @OA\Property(
     *   property="shipping_method",
     *   type="integer",
     * )
     *
     * @OA\Property(
     *   property="comment",
     *   type="string",
     *   example="asap plz",
     * )
     */

    protected $fillable = [
        'code',
        'email',
        'client_id',
        'shipping_method',
        'payment_status',
        'shop_status',
        'delivery_method',
        'delivery_status',
        'delivery_tracking',
        'comment',
    ];

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

    /**
     * @OA\Property(
     *   property="items",
     *   type="array",
     *   @OA\Items(ref="#/components/schemas/OrderItem"),
     * )
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @OA\Property(
     *   property="delivery_address",
     *   ref="#/components/schemas/Address",
     * )
     */
    public function deliveryAddress()
    {
        return $this->hasOne(Address::class, 'id', 'delivery_address');
    }

    /**
     * @OA\Property(
     *   property="invoice_address",
     *   ref="#/components/schemas/Address",
     * )
     */
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
