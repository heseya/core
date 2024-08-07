<?php

namespace App\Models;

use App\Criteria\MetadataPrivateSearch;
use App\Criteria\MetadataSearch;
use App\Criteria\WhereInIds;
use App\Traits\HasDiscounts;
use App\Traits\HasMetadata;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property float $price
 *
 * @mixin IdeHelperShippingMethod
 */
class ShippingMethod extends Model implements AuditableContract
{
    use Auditable;
    use HasCriteria;
    use HasDiscounts;
    use HasFactory;
    use HasMetadata;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'public',
        'order',
        'block_list',
        'shipping_time_min',
        'shipping_time_max',
        'shipping_type',
        'integration_key',
        'app_id',
        'shipping_type',
        'payment_on_delivery',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'public' => 'boolean',
        'block_list' => 'boolean',
        'payment_on_delivery' => 'boolean',
    ];

    /** @var array<string, class-string> */
    protected array $criteria = [
        'metadata' => MetadataSearch::class,
        'metadata_private' => MetadataPrivateSearch::class,
        'ids' => WhereInIds::class,
    ];

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'shipping_method_id', 'id');
    }

    public function digitalOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'digital_shipping_method_id', 'id');
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

    public function shippingPoints(): BelongsToMany
    {
        return $this->belongsToMany(Address::class, 'address_shipping_method');
    }

    public function getPrice(float $orderTotal): float
    {
        $priceRange = $this->priceRanges()
            ->where('start', '<=', $orderTotal)
            ->orderBy('start', 'desc')
            ->first();

        return $priceRange && $priceRange->prices()->first() ? ($priceRange->prices()->first()->value ?? 0.0) : 0.0;
    }

    public function priceRanges(): HasMany
    {
        return $this->hasMany(PriceRange::class, 'shipping_method_id');
    }

    public function getDeletableAttribute(): bool
    {
        return $this->app_id === null || $this->app_id === Auth::id();
    }
}
