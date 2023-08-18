<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
        'status' => PaymentStatus::class,
        'currency' => Currency::class,
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'method_id');
    }

    public function amount(): Attribute
    {
        return Attribute::make(
            get: fn (float|string $value, array $attributes): Money => Money::of(
                $value,
                $attributes['currency'],
            ),
            set: fn (float|Money|string $value): array => match (true) {
                $value instanceof Money => [
                    'amount' => $value->getAmount(),
                    'currency' => $value->getCurrency()->getCurrencyCode(),
                ],
                default => [
                    'amount' => $value,
                ]
            }
        );
    }
}
