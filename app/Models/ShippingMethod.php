<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @mixin IdeHelperShippingMethod
 */
class ShippingMethod extends Model implements AuditableContract
{
    use HasFactory, Auditable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'public',
        'order',
        'black_list',
        'shipping_time_min',
        'shipping_time_max',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'public' => 'boolean',
        'black_list' => 'boolean',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function paymentMethodsPublic(): BelongsToMany
    {
        return $this->paymentMethods()->where('public', true);
    }

    public function paymentMethods(): BelongsToMany
    {
        return $this->belongsToMany(PaymentMethod::class, 'shipping_method_payment_method');
    }

    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class, 'shipping_method_country');
    }

    public function getPrice(float $orderTotal): float
    {
        $priceRange = $this->priceRanges()
            ->where('start', '<=', $orderTotal)
            ->orderBy('start', 'desc')
            ->first();

        return $priceRange ? $priceRange->prices()->first()->value : 0;
    }

    public function priceRanges(): HasMany
    {
        return $this->hasMany(PriceRange::class, 'shipping_method_id');
    }
}
