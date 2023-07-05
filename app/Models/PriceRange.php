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
            get: fn (string|BigDecimal $value): Money => Money::ofMinor($value, 'PLN'),
            set: fn (Money $value): BigDecimal => $value->getMinorAmount(),
        )->shouldCache();
    }

    public function value(): Attribute
    {
        return Attribute::make(
            get: fn (string|BigDecimal $value): Money => Money::ofMinor($value, 'PLN'),
            set: fn (Money $value): BigDecimal => $value->getMinorAmount(),
        )->shouldCache();
    }

//    protected $casts = [
//        'start' => 'float',
//    ];

//    public function prices(): MorphMany
//    {
//        return $this->morphMany(Price::class, 'model');
//    }
}
