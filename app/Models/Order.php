<?php

namespace App\Models;

use App\SearchTypes\OrderSearch;
use App\Traits\Sortable;
use Heseya\Searchable\Searches\Like;
use Heseya\Searchable\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * @OA\Schema()
 */
class Order extends Model
{
    use HasFactory, Searchable, Sortable;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->code = $attributes['code'] ?? $this->generateCode();
    }

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
        $value = $this->shipping_price;

        foreach ($this->products as $item) {
            $value += ($item->price * $item->quantity);
        }

        return round($value, 2);
    }

    /**
     * Summary amount of payed.
     *
     * @OA\Property(
     *   property="summary_payed",
     *   type="float",
     *   example=199.99
     * )
     *
     * @return float
     */
    public function getPayedAmountAttribute(): float
    {
        return $this->payments()
            ->where('payed', true)
            ->sum('amount');
    }

    /**
     * @OA\Property(
     *   property="payed",
     *   type="boolean",
     *   example=true,
     * )
     *
     * @return bool
     */
    public function isPayed(): bool
    {
        return $this->summary === $this->payedAmount;
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

    public function logs(): HasMany
    {
        return $this->hasMany(OrderLog::class)->orderBy('created_at', 'DESC');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(OrderNote::class)->orderBy('created_at', 'DESC');
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

    public function generateCode(): string
    {
        do {
            $code = Str::upper(Str::random(6));
        } while (Order::where('code', $code)->exists());

        return $code;
    }
}
