<?php

namespace App;

use App\Payment;
use App\ShippingMethod;
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
     *   property="comment",
     *   type="string",
     *   example="asap plz",
     * )
     *
     * @OA\Property(
     *   property="payment_status",
     *   type="string",
     *   enum={"waiting", "proggress", "failed", "canceled"},
     * )
     *
     * @OA\Property(
     *   property="shop_status",
     *   type="string",
     *   enum={"waiting", "proggress", "failed", "canceled"},
     * )
     *
     * @OA\Property(
     *   property="shipping_status",
     *   type="string",
     *   enum={"waiting", "proggress", "failed", "canceled"},
     * )
     */

    protected $fillable = [
        'code',
        'email',
        'client_id',
        'shipping_method_id',
        'shipping_price',
        'payment_status',
        'shop_status',
        'shipping_status',
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
            $value += $item->price * $item->qty;

            foreach ($item->schemaItems as $schema_item) {
                $value += $schema_item->extra_price;
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
