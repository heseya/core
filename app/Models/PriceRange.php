<?php

namespace App\Models;

use Brick\Math\BigDecimal;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * @mixin IdeHelperPriceRange
 */
class PriceRange extends Model
{
    protected $fillable = [
        'start',
        'value',
    ];

    public function start(): Attribute
    {
        return Attribute::make(
            get: fn (BigDecimal|string $value): Money => Money::ofMinor($value, 'PLN'),
            set: fn (Money $value): BigDecimal => $value->getMinorAmount(),
        );
    }

    public function value(): Attribute
    {
        return Attribute::make(
            get: fn (BigDecimal|string $value): Money => Money::ofMinor($value, 'PLN'),
            set: fn (Money $value): BigDecimal => $value->getMinorAmount(),
        );
    }
}
