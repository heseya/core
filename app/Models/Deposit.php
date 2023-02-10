<?php

namespace App\Models;

use App\Criteria\DepositSearch;
use App\Criteria\DepositSkuSearch;
use App\Criteria\WhereInIds;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

/**
 * @mixin IdeHelperDeposit
 */
class Deposit extends Model
{
    use HasFactory;
    use HasCriteria;

    protected $fillable = [
        'quantity',
        'item_id',
        'order_product_id',
        'shipping_time',
        'shipping_date',
        'from_unlimited',
    ];

    protected $dates = [
        'shipping_date',
    ];

    protected $casts = [
        'from_unlimited' => 'bool',
    ];

    protected array $criteria = [
        'sku' => DepositSkuSearch::class,
        'search' => DepositSearch::class,
        'ids' => WhereInIds::class,
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function orderProduct(): BelongsTo
    {
        return $this->belongsTo(OrderProduct::class);
    }

    public function order(): HasOneThrough
    {
        return $this->hasOneThrough(
            Order::class,
            OrderProduct::class,
            'id',
            'id',
            'order_product_id',
            'order_id',
        );
    }
}
