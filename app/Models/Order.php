<?php

namespace App\Models;

use App\Audits\Redactors\AddressRedactor;
use App\Audits\Redactors\ShippingMethodRedactor;
use App\Audits\Redactors\StatusRedactor;
use App\SearchTypes\OrderSearch;
use App\Services\Contracts\OrderServiceContract;
use App\Services\OrderService;
use Heseya\Searchable\Searches\Like;
use Heseya\Searchable\Traits\Searchable;
use Heseya\Sortable\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @OA\Schema ()
 *
 * @mixin IdeHelperOrder
 */
class Order extends Model implements AuditableContract
{
    use HasFactory, Searchable, Sortable, Notifiable, Auditable;

    /**
     * @OA\Property(
     *   property="id",
     *   type="string",
     *   example="026bc5f6-8373-4aeb-972e-e78d72a67121",
     * )
     *
     * @OA\Property(
     *   property="email",
     *   type="string",
     *   example="admin@example.com",
     * )
     *
     * @OA\Property(
     *   property="comment",
     *   type="string",
     *   example="asap plz",
     * )
     *
     * @OA\Property(
     *   property="shipping_number",
     *   type="string",
     *   example="630552359128340015809770",
     * )
     *
     * @OA\Property(
     *   property="shipping_price",
     *   type="float",
     *   example=18.70
     * )
     */

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

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected array $searchable = [
        'search' => OrderSearch::class,
        'status_id',
        'shipping_method_id',
        'code' => Like::class,
        'email' => Like::class,
    ];

    protected array $sortable = [
        'id',
        'code',
        'created_at',
    ];

    protected string $defaultSortBy = 'created_at';
    protected string $defaultSortDirection = 'desc';

    /**
     * @OA\Property(
     *   property="summary",
     *   type="number",
     * )
     */
    public function getSummaryAttribute(): float
    {
        /** @var OrderService $orderService */
        $orderService = app(OrderServiceContract::class);

        return $orderService->calcSummary($this);
    }

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
            ->where('payed', true)
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
            ->orderBy('payed', 'DESC')
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
        return !$this->isPayed() &&
            !$this->status->cancel &&
            $this->shippingMethod->paymentMethods()->count() > 0;
    }

    /**
     * @OA\Property(
     *   property="payed",
     *   type="boolean",
     * )
     */
    public function isPayed(): bool
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
            ->withPivot(['type', 'discount']);
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

    /**
     * @OA\Property(
     *   property="user",
     *   ref="#/components/schemas/User",
     * )
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
