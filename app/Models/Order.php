<?php

namespace App\Models;

use App\Audits\Redactors\AddressRedactor;
use App\Audits\Redactors\ShippingMethodRedactor;
use App\Audits\Redactors\StatusRedactor;
use App\Criteria\MetadataPrivateSearch;
use App\Criteria\MetadataSearch;
use App\Criteria\OrderSearch;
use App\Criteria\WhereCreatedAfter;
use App\Criteria\WhereCreatedBefore;
use App\Criteria\WhereHasStatusHidden;
use App\Models\Contracts\SortableContract;
use App\Traits\HasMetadata;
use App\Traits\HasOrderDiscount;
use App\Traits\Sortable;
use Heseya\Searchable\Criteria\Like;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @mixin IdeHelperOrder
 */
class Order extends Model implements AuditableContract, SortableContract
{
    use HasFactory, HasCriteria, Sortable, Notifiable, Auditable, HasMetadata, HasOrderDiscount;

    protected $fillable = [
        'code',
        'email',
        'currency',
        'comment',
        'status_id',
        'shipping_method_id',
        'digital_shipping_method_id',
        'shipping_price_initial',
        'shipping_price',
        'shipping_number',
        'billing_address_id',
        'shipping_address_id',
        'created_at',
        'buyer_id',
        'buyer_type',
        'summary',
        'paid',
        'cart_total_initial',
        'cart_total',
        'shipping_place',
        'invoice_requested',
        'shipping_type',
    ];

    protected array $auditInclude = [
        'code',
        'email',
        'currency',
        'comment',
        'status_id',
        'shipping_method_id',
        'digital_shipping_method_id',
        'shipping_price',
        'shipping_number',
        'billing_address_id',
        'shipping_address_id',
    ];

    protected array $attributeModifiers = [
        'status_id' => StatusRedactor::class,
        'shipping_method_id' => ShippingMethodRedactor::class,
        'digital_shipping_method_id' => ShippingMethodRedactor::class,
        'billing_address_id' => AddressRedactor::class,
        'shipping_address_id' => AddressRedactor::class,
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
    ];

    /**
     * Summary amount of paid.
     */
    public function getPaidAmountAttribute(): float
    {
        return $this->payments
            ->where('paid', true)
            ->sum('amount');
    }

    public function payments(): HasMany
    {
        return $this
            ->hasMany(Payment::class)
            ->orderBy('paid', 'DESC')
            ->orderBy('updated_at', 'DESC');
    }

    public function getPayableAttribute(): bool
    {
        $paymentMethodCount = $this->shippingMethod?->paymentMethods->count()
            ?? $this->digitalShippingMethod?->paymentMethods->count()
            ?? 0;

        return !$this->paid &&
            $this->status !== null &&
            !$this->status->cancel &&
            $paymentMethodCount > 0;
    }

    public function getShippingTypeAttribute(): string|null
    {
        return $this->shippingMethod
            ? $this->shippingMethod->shipping_type
            : ($this->digitalShippingMethod ? $this->digitalShippingMethod->shipping_type : null);
    }

    public function isPaid(): bool
    {
        return $this->paid_amount >= $this->summary;
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

    /**
     * @param array $items
     */
    public function saveItems($items): void
    {
        foreach ($items as $item) {
            $item = OrderProduct::create($item);
            $this->products()->save($item);
        }
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

    public function generateCode(): string
    {
        do {
            $code = Str::upper(Str::random(6));
        } while (Order::query()->where('code', $code)->exists());

        return $code;
    }

    public function buyer(): MorphTo
    {
        return $this->morphTo('order', 'buyer_type', 'buyer_id', 'id');
    }

    public function preferredLocale(): string
    {
        $country = Str::of($this->deliveryAddress?->country ?? '')->limit(2, '')->lower();

        if ($country->is('pl')) {
            return 'pl';
        }

        return 'en';
    }
}
