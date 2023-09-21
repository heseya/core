<?php

declare(strict_types=1);

namespace Domain\ShippingMethod\Models;

use App\Criteria\MetadataPrivateSearch;
use App\Criteria\MetadataSearch;
use App\Criteria\WhereInIds;
use App\Enums\ShippingType;
use App\Models\Address;
use App\Models\App;
use App\Models\Country;
use App\Models\IdeHelperShippingMethod;
use App\Models\Model;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PriceRange;
use App\Traits\HasDiscounts;
use App\Traits\HasMetadata;
use Brick\Math\BigDecimal;
use Brick\Money\Money;
use Domain\SalesChannel\Models\SalesChannel;
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
final class ShippingMethod extends Model
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
        'shipping_type' => ShippingType::class,
        'payment_on_delivery' => 'boolean',
    ];
    /** @var array<string, class-string> */
    protected array $criteria = [
        'metadata' => MetadataSearch::class,
        'metadata_private' => MetadataPrivateSearch::class,
        'ids' => WhereInIds::class,
    ];

    /**
     * @return BelongsTo<App, self>
     */
    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }

    /**
     * @return HasMany<Order>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'shipping_method_id', 'id');
    }

    /**
     * @return HasMany<Order>
     */
    public function digitalOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'digital_shipping_method_id', 'id');
    }

    /**
     * @return BelongsToMany<PaymentMethod>
     */
    public function paymentMethodsPublic(): BelongsToMany
    {
        return $this->paymentMethods()->where('public', true);
    }

    /**
     * @return BelongsToMany<PaymentMethod>
     */
    public function paymentMethods(): BelongsToMany
    {
        return $this->belongsToMany(PaymentMethod::class, 'shipping_method_payment_method');
    }

    /**
     * @return BelongsToMany<Country>
     */
    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(
            Country::class,
            'shipping_method_country',
            'shipping_method_id',
            'country_code',
            'id',
            'code',
            'shippingMethods',
        );
    }

    public function isCountryBlocked(string $country): bool
    {
        if ($this->countries->count() === 0) {
            return false;
        }

        return $this->block_list
            ? $this->countries->contains('code', $country)
            : !$this->countries->contains('code', $country);
    }

    /**
     * @return BelongsToMany<Address>
     */
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
     * @return HasMany<PriceRange>
     */
    public function priceRanges(): HasMany
    {
        return $this->hasMany(PriceRange::class, 'shipping_method_id');
    }

    /**
     * @return BelongsToMany<SalesChannel>
     */
    public function salesChannels(): BelongsToMany
    {
        return $this->belongsToMany(
            SalesChannel::class,
            'sales_channels_shipping_methods',
            'shipping_method_id',
            'sales_channel_id',
            'id',
            'id',
        );
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

    public function getDeletableAttribute(): bool
    {
        return $this->app_id === null || $this->app_id === Auth::id();
    }
}
