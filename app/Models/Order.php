<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema()
 */
class Order extends Model
{
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
     *   property="comment",
     *   type="string",
     *   example="asap plz",
     * )
     */

    protected $fillable = [
        'code',
        'email',
        'client_id',
        'shipping_method_id',
        'shipping_price',
        'status_id',
        'delivery_address_id',
        'invoice_address_id',
        'comment',
    ];

    /**
     * @OA\Property(
     *   property="summary",
     *   type="number",
     * )
    */
    public function getSummaryAttribute()
    {
        $value = $this->shipping_price;

        foreach ($this->items as $item) {
            $value += ($item->price * $item->quantity);
        }

        return round($value, 2);
    }

    public function saveItems($items)
    {
        foreach ($items as $item) {
            $item = OrderItem::create($item);
            $this->items()->save($item);
        }
    }

    /**
     * @OA\Property(
     *   property="status",
     *   ref="#/components/schemas/Status",
     * )
     */
    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    /**
     * @OA\Property(
     *   property="shipping_method",
     *   ref="#/components/schemas/ShippingMethod",
     * )
     */
    public function shippingMethod()
    {
        return $this->hasOne(ShippingMethod::class, 'id', 'shipping_method_id');
    }

    /**
     * @OA\Property(
     *   property="delivery_address",
     *   ref="#/components/schemas/Address",
     * )
     */
    public function deliveryAddress()
    {
        return $this->hasOne(Address::class, 'id', 'delivery_address_id');
    }

    /**
     * @OA\Property(
     *   property="invoice_address",
     *   ref="#/components/schemas/Address",
     * )
     */
    public function invoiceAddress()
    {
        return $this->hasOne(Address::class, 'id', 'invoice_address_id');
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
