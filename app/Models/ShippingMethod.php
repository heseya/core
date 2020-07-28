<?php

namespace App\Models;

/**
 * @OA\Schema()
 */
class ShippingMethod extends Model
{
    /**
     * @OA\Property(
     *   property="id",
     *   type="integer",
     * )
     *
     * @OA\Property(
     *   property="name",
     *   type="string",
     *   example="Next Day Courier",
     * )
     *
     * @OA\Property(
     *   property="price",
     *   type="number",
     *   example=10.99,
     * )
     *
     * @OA\Property(
     *   property="public",
     *   type="boolean",
     * )
     */

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'price',
        'public',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'price' => 'float',
        'public' => 'boolean',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @OA\Property(
     *   property="payment_methods",
     *   type="array",
     *   @OA\Items(ref="#/components/schemas/PaymentMethod"),
     * )
     */
    public function paymentMethods()
    {
        return $this->belongsToMany(PaymentMethod::class, 'shipping_method_payment_method');
    }

    public function paymentMethodsPublic()
    {
        return $this->paymentMethods()->where('public', true);
    }
}
