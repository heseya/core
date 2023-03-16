<?php

namespace App\Models;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @mixin IdeHelperPrice
 */
class Price extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'value',
        'model_id',
        'model_type',
        'price_type',
    ];

    protected $casts = [
        'value' => 'float',
    ];

//    public function value(): Attribute
//    {
//        return Attribute::make(
//            get: fn (string $value): Money => Money::ofMinor($value, 'PLN'),
//            set: fn (Money $value): string => (string) $value->getMinorAmount(),
//        )->shouldCache();
//    }
//
//    public function model(): MorphTo
//    {
//        return $this->morphTo();
//    }
}
