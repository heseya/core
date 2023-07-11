<?php

namespace App\Models;

use Brick\Math\BigDecimal;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * @mixin IdeHelperPrice
 */
class Price extends Model
{
    protected $fillable = [
        'value',
        'model_id',
        'model_type',
        'price_type',
    ];

    public function value(): Attribute
    {
        return Attribute::make(
            get: fn (BigDecimal|string $value): Money => Money::ofMinor($value, 'PLN'),
            set: fn (Money $value): BigDecimal => $value->getMinorAmount(),
        );
    }
}
