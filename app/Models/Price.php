<?php

namespace App\Models;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @mixin IdeHelperPrice
 */
class Price extends Model
{
    protected $fillable = [
        'value',
        'currency',
        'model_id',
        'model_type',
        'price_type',
    ];

    public function value(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): Money => Money::ofMinor(
                $attributes['value'],
                $attributes['currency'],
            ),
            set: fn (Money $value): array => [
                'value' => $value->getMinorAmount(),
                'currency' => $value->getCurrency()->getCurrencyCode(),
            ],
        );
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }
}
