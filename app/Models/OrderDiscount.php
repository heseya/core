<?php

namespace App\Models;

use Brick\Money\Money;
use Domain\Currency\Currency;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property ?Money $applied
 * @property ?Money $amount
 *
 * @mixin IdeHelperOrderDiscount
 */
class OrderDiscount extends MorphPivot
{
    protected $table = 'order_discounts';

    protected $casts = [
        'currency' => Currency::class,
    ];

    protected function applied(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): ?Money => $value !== null ? Money::ofMinor(
                $value,
                $attributes['currency'],
            ) : null,
            set: fn (int|Money|string|null $value): array => match (true) {
                $value instanceof Money => [
                    'applied' => $value->getMinorAmount(),
                    'currency' => $value->getCurrency()->getCurrencyCode(),
                ],
                default => [
                    'applied' => $value,
                ],
            },
        );
    }

    protected function amount(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): ?Money => $value !== null ? Money::ofMinor(
                $value,
                $attributes['currency'],
            ) : null,
            set: fn (int|Money|string|null $value): array => match (true) {
                $value instanceof Money => [
                    'amount' => $value->getMinorAmount(),
                    'currency' => $value->getCurrency()->getCurrencyCode(),
                ],
                default => [
                    'amount' => $value,
                ],
            },
        );
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class, 'discount_id');
    }
}
