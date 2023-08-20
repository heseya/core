<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\PaymentStatus;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property PaymentStatus $status
 * @property Money $amount
 * @property Currency $currency
 *
 * @mixin IdeHelperPayment
 */
class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'method',
        'currency',
        'amount',
        'redirect_url',
        'continue_url',
        'created_at',
        'status',
        'order_id',
        'method_id',
    ];

    protected $casts = [
        'amount' => MoneyCast::class,
        'status' => PaymentStatus::class,
        'currency' => Currency::class,
    ];

    /**
     * @return BelongsTo<Order, self>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<PaymentMethod, self>
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'method_id');
    }
}
