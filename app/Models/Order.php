<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Criteria\MetadataPrivateSearch;
use App\Criteria\MetadataSearch;
use App\Criteria\OrderPayments;
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
use Brick\Math\Exception\MathException;
use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\Organization\Models\Organization;
use Domain\PaymentMethods\Enums\PaymentMethodType;
use Domain\PaymentMethods\Models\PaymentMethod;
use Domain\SalesChannel\Models\SalesChannel;
use Domain\ShippingMethod\Models\ShippingMethod;
use Heseya\Searchable\Criteria\Like;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property Money $summary
 * @property Currency $currency
 *
 * @mixin IdeHelperOrder
 */
final class Order extends Model implements SortableContract
{
    use HasCriteria;
    use HasFactory;
    use HasMetadata;
    use HasOrderDiscount;
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
        'sales_channel_id',
        'currency',
        'cart_total_initial',
        'cart_total',
        'shipping_price_initial',
        'shipping_price',
        'summary',
        'language',
        'payment_method_type',
        'organization_id',
    ];

    protected array $criteria = [
        'search' => OrderSearch::class,
        'status_id',
        'shipping_method_id',
        'digital_shipping_method_id',
        'sales_channel_id',
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
        'payment_method_id' => OrderPayments::class,
        'organization_id',
    ];

    protected array $sortable = [
        'id',
        'code',
        'created_at',
        'email',
        'summary',
    ];

    protected $casts = [
        'paid' => 'boolean',
        'invoice_requested' => 'boolean',
        'currency' => Currency::class,
        'cart_total_initial' => MoneyCast::class,
        'cart_total' => MoneyCast::class,
        'shipping_price_initial' => MoneyCast::class,
        'shipping_price' => MoneyCast::class,
        'summary' => MoneyCast::class,
        'payment_method_type' => PaymentMethodType::class,
    ];

    /**
     * Summary amount of paid.
     */
    public function getPaidAmountAttribute(): Money
    {
        return $this->payments
            ->where('status', PaymentStatus::SUCCESSFUL)
            ->reduce(
                fn (Money $carry, Payment $payment) => $carry->plus($payment->amount),
                Money::zero($this->currency->value),
            );
    }

    /**
     * @return HasMany<Payment>
     */
    public function payments(): HasMany
    {
        return $this
            ->hasMany(Payment::class)
            ->orderBy('status', 'ASC')
            ->orderBy('updated_at', 'DESC');
    }

    public function getPaymentMethodAttribute(): ?PaymentMethod
    {
        /** @var Payment|null $payment */
        $payment = $this->payments
            ->sortBy('created_at')
            ->first();

        return $payment?->paymentMethod;
    }

    public function getPayableAttribute(): bool
    {
        if ($this->payment_method_type === PaymentMethodType::POSTPAID) {
            return false;
        }

        return !$this->paid
            && $this->status !== null
            && !$this->status->cancel;
    }

    public function getShippingTypeAttribute(): ?ShippingType
    {
        return $this->shippingMethod
            ? $this->shippingMethod->shipping_type
            : ($this->digitalShippingMethod ? $this->digitalShippingMethod->shipping_type : null);
    }

    /**
     * @throws MathException
     * @throws MoneyMismatchException
     */
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
        return $this->belongsTo(ShippingMethod::class, 'shipping_method_id')->withTrashed();
    }

    public function digitalShippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class, 'digital_shipping_method_id')->withTrashed();
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

    public function salesChannel(): HasOne
    {
        return $this->hasOne(SalesChannel::class, 'id', 'sales_channel_id');
    }

    public function organization(): HasOne
    {
        return $this->hasOne(Organization::class, 'id', 'organization_id');
    }

    public function getLocaleAttribute(): string
    {
        return explode('-', $this->language)[0];
    }
}
