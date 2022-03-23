<?php

namespace App\Models;

use App\Audits\Redactors\AddressRedactor;
use App\Audits\Redactors\ShippingMethodRedactor;
use App\Audits\Redactors\StatusRedactor;
use App\SearchTypes\MetadataPrivateSearch;
use App\SearchTypes\MetadataSearch;
use App\SearchTypes\OrderSearch;
use App\SearchTypes\WhereCreatedAfter;
use App\SearchTypes\WhereCreatedBefore;
use App\SearchTypes\WhereHasStatusHidden;
use App\Traits\HasMetadata;
use Heseya\Searchable\Searches\Like;
use Heseya\Searchable\Traits\Searchable;
use Heseya\Sortable\Sortable;
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
class Order extends Model implements AuditableContract
{
    use HasFactory, Searchable, Sortable, Notifiable, Auditable, HasMetadata;

    protected $fillable = [
        'code',
        'email',
        'currency',
        'comment',
        'status_id',
        'shipping_method_id',
        'shipping_price',
        'shipping_number',
        'delivery_address_id',
        'invoice_address_id',
        'created_at',
        'user_id',
        'user_type',
        'summary',
        'paid',
    ];

    protected $auditInclude = [
        'code',
        'email',
        'currency',
        'comment',
        'status_id',
        'shipping_method_id',
        'shipping_price',
        'shipping_number',
        'delivery_address_id',
        'invoice_address_id',
    ];

    protected $attributeModifiers = [
        'status_id' => StatusRedactor::class,
        'shipping_method_id' => ShippingMethodRedactor::class,
        'delivery_address_id' => AddressRedactor::class,
        'invoice_address_id' => AddressRedactor::class,
    ];

    protected array $searchable = [
        'search' => OrderSearch::class,
        'status_id',
        'shipping_method_id',
        'code' => Like::class,
        'email' => Like::class,
        'user_id',
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
    ];

    /**
     * Summary amount of paid.
     *
     * @OA\Property(
     *   property="summary_paid",
     *   type="float",
     *   example=199.99
     * )
     */
    public function getPaidAmountAttribute(): float
    {
        return $this->payments
            ->where('paid', true)
            ->sum('amount');
    }

    /**
     * @OA\Property(
     *   property="payments",
     *   type="array",
     *   @OA\Items(ref="#/components/schemas/Payment"),
     * )
     */
    public function payments(): HasMany
    {
        return $this
            ->hasMany(Payment::class)
            ->orderBy('paid', 'DESC')
            ->orderBy('updated_at', 'DESC');
    }

    /**
     * @OA\Property(
     *   property="payable",
     *   type="boolean",
     * )
     */
    public function getPayableAttribute(): bool
    {
        return !$this->paid &&
            !$this->status->cancel &&
            $this->shippingMethod->paymentMethods()->count() > 0;
    }

    public function isPaid(): bool
    {
        return $this->paid_amount >= $this->summary;
    }

    /**
     * @OA\Property(
     *   property="status",
     *   ref="#/components/schemas/Status",
     * )
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    /**
     * @OA\Property(
     *   property="shipping_method",
     *   ref="#/components/schemas/ShippingMethod",
     * )
     */
    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    /**
     * @OA\Property(
     *   property="delivery_address",
     *   ref="#/components/schemas/Address",
     * )
     */
    public function deliveryAddress(): HasOne
    {
        return $this->hasOne(Address::class, 'id', 'delivery_address_id');
    }

    /**
     * @OA\Property(
     *   property="invoice_address",
     *   ref="#/components/schemas/Address",
     * )
     */
    public function invoiceAddress(): HasOne
    {
        return $this->hasOne(Address::class, 'id', 'invoice_address_id');
    }

    public function deposits(): HasManyThrough
    {
        return $this->hasManyThrough(Deposit::class, OrderProduct::class);
    }

    public function discounts(): BelongsToMany
    {
        return $this
            ->belongsToMany(Discount::class, 'order_discounts')
            ->withPivot(['type', 'discount'])
            ->withTrashed();
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

    /**
     * @OA\Property(
     *   property="products",
     *   type="array",
     *   @OA\Items(ref="#/components/schemas/OrderProduct"),
     * )
     */
    public function products(): HasMany
    {
        return $this->hasMany(OrderProduct::class);
    }

    public function generateCode(): string
    {
        do {
            $code = Str::upper(Str::random(6));
        } while (Order::where('code', $code)->exists());

        return $code;
    }

    public function user(): MorphTo
    {
        return $this->morphTo('order', 'user_type', 'user_id', 'id');
    }
}
