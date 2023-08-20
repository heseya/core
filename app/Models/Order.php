<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Criteria\MetadataPrivateSearch;
use App\Criteria\MetadataSearch;
use App\Criteria\OrderSearch;
use App\Criteria\WhereCreatedAfter;
use App\Criteria\WhereCreatedBefore;
use App\Criteria\WhereHasStatusHidden;
use App\Criteria\WhereInIds;
use App\Enums\PaymentStatus;
use App\Enums\ShippingType;
use App\Models\Contracts\SortableContract;
use App\Traits\HasMetadata;
use App\Traits\HasOrderDiscount;
use App\Traits\Sortable;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Heseya\Searchable\Criteria\Like;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

/**
 * @mixin IdeHelperOrder
 */
class Order extends Model implements SortableContract
{
    use HasCriteria;
    use HasFactory;
    use HasMetadata;
    use HasOrderDiscount;
    use Notifiable;
    use Sortable;

    protected $fillable = [
        'code',
        'email',
        'comment',
        'status_id',
        'shipping_method_id',
        'digital_shipping_method_id',
        'shipping_number',
        'billing_address_id',
        'shipping_address_id',
        'created_at',
        'buyer_id',
        'buyer_type',
        'paid',
        'shipping_place',
        'invoice_requested',
        'shipping_type',

        'currency',
        'cart_total_initial',
        'cart_total',
        'shipping_price_initial',
        'shipping_price',
        'summary',
    ];

    protected array $criteria = [
        'search' => OrderSearch::class,
        'status_id',
        'shipping_method_id',
        'digital_shipping_method_id',
        'code' => Like::class,
        'email' => Like::class,
        'buyer_id',
        'status.hidden' => WhereHasStatusHidden::class,
        'paid',
        'from' => WhereCreatedAfter::class,
        'to' => WhereCreatedBefore::class,
        'metadata' => MetadataSearch::class,
        'metadata_private' => MetadataPrivateSearch::class,
        'ids' => WhereInIds::class,
    ];

    protected array $sortable = [
        'id',
        'code',
        'created_at',
        'email',

        'summary',
    ];

    protected string $defaultSortBy = 'created_at';
    protected string $defaultSortDirection = 'desc';

    protected $casts = [
        'paid' => 'boolean',
        'invoice_requested' => 'boolean',
        'currency' => Currency::class,
        'cart_total_initial' => MoneyCast::class,
        'cart_total' => MoneyCast::class,
        'shipping_price_initial' => MoneyCast::class,
        'shipping_price' => MoneyCast::class,
        'summary' => MoneyCast::class,
    ];

    /**
     * Summary amount of paid.
     */
    public function getPaidAmountAttribute(): Money
    {
        return $this->payments
            ->where('status', PaymentStatus::SUCCESSFUL)
            ->reduce(
                fn (Money $carry, Payment $payment) => $carry->plus(
                    // TODO: This should use money in payment instead of floats
                    Money::of($payment->amount, $this->currency->value),
                ),
                Money::zero($this->currency->value),
            );
    }

    public function payments(): HasMany
    {
        return $this
            ->hasMany(Payment::class)
            ->orderBy('status', 'ASC')
            ->orderBy('updated_at', 'DESC');
    }

    public function getPayableAttribute(): bool
    {
        $paymentMethodCount = $this->shippingMethod?->paymentMethods->count()
            ?? $this->digitalShippingMethod?->paymentMethods->count()
            ?? 0;

        return !$this->paid
            && $this->status !== null
            && !$this->status->cancel
            && $paymentMethodCount > 0;
    }

    public function getShippingTypeAttribute(): ?ShippingType
    {
        return $this->shippingMethod
            ? $this->shippingMethod->shipping_type
            : ($this->digitalShippingMethod ? $this->digitalShippingMethod->shipping_type : null);
    }

    public function isPaid(): bool
    {
        return $this->paid_amount->isGreaterThanOrEqualTo($this->summary);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class, 'shipping_method_id');
    }

    public function digitalShippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class, 'digital_shipping_method_id');
    }

    public function shippingAddress(): HasOne
    {
        return $this->hasOne(Address::class, 'id', 'shipping_address_id');
    }

    public function invoiceAddress(): HasOne
    {
        return $this->hasOne(Address::class, 'id', 'billing_address_id');
    }

    public function deposits(): HasManyThrough
    {
        return $this->hasManyThrough(Deposit::class, OrderProduct::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(OrderProduct::class);
    }

    public function documents(): BelongsToMany
    {
        return $this
            ->belongsToMany(Media::class, 'order_document', 'order_id', 'media_id')
            ->using(OrderDocument::class)
            ->withPivot('id', 'type', 'name');
    }

    public function buyer(): MorphTo
    {
        return $this->morphTo('order', 'buyer_type', 'buyer_id', 'id');
    }

    public function preferredLocale(): string
    {
        $country = Str::of($this->shippingAddress?->country ?? '')
            ->limit(2, '')
            ->lower()
            ->toString();

        return match ($country) {
            'pl', 'en' => $country,
            default => Config::get('app.locale'),
        };
    }

    //    private static function priceAttributeTemplate(string $fieldName): Attribute
    //    {
    //        return Attribute::make(
    //            get: fn (mixed $value, array $attributes): Money => Money::ofMinor(
    //                $attributes[$fieldName],
    //                $attributes['currency'],
    //            ),
    //            set: fn (int|Money|string $value): array => match (true) {
    //                $value instanceof Money => [
    //                    $fieldName => $value->getMinorAmount(),
    //                    'currency' => $value->getCurrency()->getCurrencyCode(),
    //                ],
    //                default => [
    //                    $fieldName => $value,
    //                ]
    //            }
    //        );
    //    }
}
