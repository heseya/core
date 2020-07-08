<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
        'currency',
        'comment',
        'status_id',
        'shipping_method_id',
        'shipping_price',
        'delivery_address_id',
        'invoice_address_id',
    ];

    /**
     * @OA\Property(
     *   property="summary",
     *   type="number",
     * )
    */
    public function getSummaryAttribute(): float
    {
        $value = $this->shipping_price;

        foreach ($this->items as $item) {
            $value += ($item->price * $item->quantity);
        }

        return round($value, 2);
    }

    /**
     * Summary amount of payed.
     *
     * @OA\Property(
     *   property="summary_payed",
     *   type="number",
     * )
     *
     * @return float
     */
    public function getPayedAmountAttribute(): float
    {
        return $this->payments()
            ->where('status', Payment::STATUS_PAYED)
            ->sum('amount');
    }

    /**
     * @OA\Property(
     *   property="payed",
     *   type="bolean",
     * )
     *
     * @return bool
     */
    public function isPayed(): bool
    {
        return $this->summary === $this->payed;
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
        return $this->belongsTo(ShippingMethod::class);
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

    /**
     * @OA\Property(
     *   property="payments",
     *   type="array",
     *   @OA\Items(ref="#/components/schemas/Payment"),
     * )
     */
    public function payments()
    {
        return $this
            ->hasMany(Payment::class)
            ->orderBy('payed', 'DESC')
            ->orderBy('updated_at', 'DESC');
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

    /**
     * @param $items
     */
    public function saveItems($items): void
    {
        foreach ($items as $item) {
            $item = OrderItem::create($item);
            $this->items()->save($item);
        }
    }
}
