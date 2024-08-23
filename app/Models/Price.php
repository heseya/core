<?php

namespace App\Models;

use Brick\Money\Money;
use Database\Factories\PriceFactory;
use Domain\Currency\Currency;
use Domain\Price\Dtos\PriceDto;
use Domain\Price\Enums\DiscountConditionPriceType;
use Domain\Price\Enums\ProductPriceType;
use Domain\PriceMap\PriceMap;
use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as LaravelModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\LaravelData\WithData;

/**
 * @property Money $value
 * @property Money $net
 * @property Money $gross
 * @property Currency $currency
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
        'net',
        'gross',
        'currency',
        'model_id',
        'model_type',
        'price_type',
        'is_net',
        'sales_channel_id',
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

    public function net(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): Money => Money::ofMinor(
                $attributes['net'] ?? 0,
                $attributes['currency'],
            ),
            set: fn (int|Money|string $value): array => match (true) {
                $value instanceof Money => [
                    'net' => $value->getMinorAmount(),
                    'currency' => $value->getCurrency()->getCurrencyCode(),
                ],
                default => [
                    'net' => $value,
                ],
            },
        );
    }

    public function gross(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): Money => Money::ofMinor(
                $attributes['gross'] ?? 0,
                $attributes['currency'],
            ),
            set: fn (int|Money|string $value): array => match (true) {
                $value instanceof Money => [
                    'gross' => $value->getMinorAmount(),
                    'currency' => $value->getCurrency()->getCurrencyCode(),
                ],
                default => [
                    'gross' => $value,
                ],
            },
        );
    }

    /**
     * @return MorphTo<Option|Product|Schema|Discount|LaravelModel,self>
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<SalesChannel,self>
     */
    public function salesChannel(): BelongsTo
    {
        return $this->belongsTo(SalesChannel::class);
    }

    /**
     * @return BelongsTo<PriceMap,SalesChannel>|null
     */
    public function priceMap(): ?BelongsTo
    {
        return $this->salesChannel?->priceMap();
    }

    /**
     * @return Builder<self>
     */
    public function scopeOfPriceMap(Builder $query, PriceMap|string $price_map_id): Builder
    {
        return $query->whereHas('salesChannel', fn (Builder $subquery) => $subquery->where('price_map_id', $price_map_id instanceof PriceMap ? $price_map_id->id : $price_map_id));
    }

    /**
     * @return Builder<self>
     */
    public function scopeOfSalesChannel(Builder $query, SalesChannel|string $sales_channel_id): Builder
    {
        return $query->where('sales_channel_id', $sales_channel_id instanceof SalesChannel ? $sales_channel_id->id : $sales_channel_id);
    }

    /**
     * @return Builder<self>
     */
    public function scopeOfCurrency(Builder $query, Currency|string $currency): Builder
    {
        return $query->where('currency', $currency instanceof Currency ? $currency->value : $currency);
    }

    /**
     * @return Builder<self>
     */
    public function scopeOfType(Builder $query, DiscountConditionPriceType|ProductPriceType $type): Builder
    {
        return $query->where('type', $type->value);
    }
}
