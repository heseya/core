<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @OA\Schema()
 */
class ShippingMethod extends Model
{
    use HasFactory;

    /**
     * @OA\Property(
     *   property="id",
     *   type="string",
     *   example="026bc5f6-8373-4aeb-972e-e78d72a67121",
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
     *
     * @OA\Property(
     *   property="black_list",
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
        'order',
        'black_list',
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

    public function orders(): HasMany
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
    public function paymentMethods(): BelongsToMany
    {
        return $this->belongsToMany(PaymentMethod::class, 'shipping_method_payment_method');
    }

    public function paymentMethodsPublic(): BelongsToMany
    {
        return $this->paymentMethods()->where('public', true);
    }

    /**
     * @OA\Property(
     *   property="countries",
     *   type="array",
     *   @OA\Items(ref="#/components/schemas/Country"),
     * )
     */
    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class, 'shipping_method_country');
    }
}
