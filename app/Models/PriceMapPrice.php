<?php

namespace App\Models;

use Brick\Money\Money;
use Database\Factories\PriceFactory;
use Domain\Currency\Currency;
use Domain\Price\Dtos\PriceDto;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\LaravelData\WithData;

/**
 * @property Money $value
 * @property string $currency
 *
 * @method static PriceFactory factory()
 *
 * @mixin IdeHelperPriceMapPrice
 */
class PriceMapPrice extends Model
{
    use HasFactory;
    use WithData;

    protected string $dataClass = PriceDto::class;

    protected $fillable = [
        'value',
        'currency',
        'price_map_id',
        'model_id',
        'model_type',
        'is_net',
    ];

    protected $casts = [
        'is_net' => 'bool',
        'currency' => Currency::class,
    ];

    public function value(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): Money => Money::ofMinor(
                $attributes['value'],
                $attributes['currency'],
            ),
            set: fn (int|Money|string $value): array => match (true) {
                $value instanceof Money => [
                    'value' => $value->getMinorAmount(),
                    'currency' => $value->getCurrency()->getCurrencyCode(),
                ],
                default => [
                    'value' => $value,
                ],
            },
        );
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function map(): BelongsTo
    {
        return $this->belongsTo(PriceMap::class, 'price_map_id');
    }
}
