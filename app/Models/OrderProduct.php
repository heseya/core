<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Criteria\WhereHasBuyer;
use App\Criteria\WhereOrderProductPaid;
use App\Traits\HasOrderDiscount;
use App\Traits\Sortable;
use Domain\Currency\Currency;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property Currency $currency
 *
 * @mixin IdeHelperOrderProduct
 */
class OrderProduct extends Model
{
    use HasCriteria;
    use HasFactory;
    use HasOrderDiscount;
    use Sortable;

    protected $fillable = [
        'quantity',
        'order_id',
        'product_id',
        'name',
        'vat_rate',
        'shipping_digital',
        'is_delivered',

        'currency',
        'base_price_initial',
        'base_price',
        'price_initial',
        'price',
    ];

    protected $casts = [
        'vat_rate' => 'float',
        'shipping_digital' => 'boolean',
        'is_delivered' => 'boolean',
        'currency' => Currency::class,
        'base_price_initial' => MoneyCast::class,
        'base_price' => MoneyCast::class,
        'price_initial' => MoneyCast::class,
        'price' => MoneyCast::class,
    ];

    protected array $criteria = [
        'shipping_digital',
        'user' => WhereHasBuyer::class,
        'app' => WhereHasBuyer::class,
        'product_id',
        'paid' => WhereOrderProductPaid::class,
    ];

    protected array $sortable = [
        'created_at',
    ];

    public function schemas(): HasMany
    {
        return $this->hasMany(OrderSchema::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }

    public function urls(): HasMany
    {
        return $this->hasMany(OrderProductUrl::class);
    }
}
