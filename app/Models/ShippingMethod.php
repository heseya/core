<?php

namespace App\Models;

use App\Criteria\MetadataPrivateSearch;
use App\Criteria\MetadataSearch;
use App\Criteria\WhereInIds;
use App\Enums\ShippingType;
use App\Traits\HasDiscounts;
use App\Traits\HasMetadata;
use Brick\Math\BigDecimal;
use Brick\Money\Money;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

/**
 * @property Money[] $prices
 * @property ?ShippingType $shipping_type
 *
 * @mixin IdeHelperShippingMethod
 */
class ShippingMethod extends Model
{
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
    ];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'public' => 'boolean',
        'block_list' => 'boolean',
        'shipping_type' => ShippingType::class,
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

    public function getPrice(Money $orderTotal): Money
    {
        /** @var PriceRange $priceRange */
        $priceRange = $this->priceRanges()
            ->where('currency', '=', $orderTotal->getCurrency()->getCurrencyCode())
            ->where('start', '<=', $orderTotal->getMinorAmount())
            ->orderBy('start', 'desc')
            ->firstOrFail();

        return $priceRange->value;
    }

    /**
     * @return Money[]
     */
    public function getStartingPrices(): array
    {
        $priceRanges = $this->priceRanges()
            ->where('start', '=', BigDecimal::zero())
            ->get();

        return $priceRanges->map(fn (PriceRange $range) => $range->value)->toArray();
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
