<?php

namespace App\Models;

use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;
use Brick\Money\AbstractMoney;
use Brick\Money\Money;
use Database\Factories\PriceFactory;
use Domain\Price\Dtos\PriceDto;
use Domain\SalesChannel\Models\SalesChannel;
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
 * @mixin IdeHelperPrice
 */
class Price extends Model
{
    use HasFactory;
    use WithData;

    protected string $dataClass = PriceDto::class;

    protected $fillable = [
        'value',
        'currency',
        'model_id',
        'model_type',
        'price_type',
        'is_net',
        'sales_channel_id',
    ];

    protected $casts = [
        'is_net' => 'bool',
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
                ]
            }
        );
    }

    /**
     * @psalm-param RoundingMode::* $roundingMode
     */
    public function plus(AbstractMoney|BigNumber|float|int|string $that, int $roundingMode = RoundingMode::UNNECESSARY): self
    {
        $this->value = $this->value->plus($that, $roundingMode);

        return $this;
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function salesChannel(): BelongsTo
    {
        return $this->belongsTo(SalesChannel::class, 'sales_channel_id');
    }
}
