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
     */
    protected $fillable = [
        'name',
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
        'black_list' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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

    /**
     * @OA\Property(
     *   property="price_ranges (request)",
     *   type="array",
     *   @OA\Items(
     *     type="object",
     *     @OA\Property(
     *       property="start",
     *       description="start of the range (min = 0); range goes from start to start of next range or infinity",
     *       type="number",
     *       example=0.0
     *     ),
     *     @OA\Property(
     *       property="value",
     *       description="price in this range",
     *       type="number",
     *       example=18.70
     *     ),
     *   ),
     * )
     */

    /**
     * @OA\Property(
     *   property="price_ranges (response)",
     *   type="array",
     *   @OA\Items(ref="#/components/schemas/PriceRange"),
     * )
     */
    public function priceRanges(): HasMany
    {
        return $this->hasMany(PriceRange::class, 'shipping_method_id');
    }

    public function getPrice(float $orderTotal): float
    {
        $priceRange = $this->priceRanges()
            ->where('start', '<=', $orderTotal)
            ->orderBy('start', 'desc')
            ->first();

        return $priceRange ? $priceRange->prices()->first()->value : 0;
    }
}
