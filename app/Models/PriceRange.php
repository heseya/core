<?php

namespace App\Models;

use Brick\Math\BigDecimal;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * @property Money $start
 * @property Money $value
 *
 * @mixin IdeHelperPriceRange
 */
class PriceRange extends Model
{
    protected $fillable = [
        'start',
        'value',
        'currency',
    ];

    public function start(): Attribute
    {
        return self::priceAttribute('start');
    }

    public function value(): Attribute
    {
        return self::priceAttribute('value');
    }

    private static function priceAttribute(string $attributeName): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): Money => Money::ofMinor(
                $attributes[$attributeName],
                $attributes['currency'],
            ),
            set: fn (Money $value): array => [
                $attributeName => $value->getMinorAmount(),
                'currency' => $value->getCurrency()->getCurrencyCode(),
            ],
        );
    }
}
