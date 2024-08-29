<?php

declare(strict_types=1);

namespace Domain\PriceMap;

use App\Models\Model;
use App\Models\Option;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Money $value
 *
 * @mixin IdeHelperPriceMapSchemaOptionPrice
 */
final class PriceMapSchemaOptionPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'currency',
        'is_net',
        'option_id',
        'price_map_id',
        'value',
    ];

    protected $casts = [
        'currency' => Currency::class,
        'is_net' => 'bool',
    ];

    /**
     * @return Attribute<Money,int|Money|string>
     */
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

    /**
     * @return BelongsTo<Option,self>
     */
    public function option(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'option_id');
    }

    /**
     * @return BelongsTo<PriceMap,self>
     */
    public function map(): BelongsTo
    {
        return $this->belongsTo(PriceMap::class, 'price_map_id');
    }

    /**
     * @param Builder<self> $query
     *
     * @return Builder<self>
     */
    public function scopeOfPriceMap(Builder $query, PriceMap|string $priceMapId): Builder
    {
        return $query->where('price_map_id', $priceMapId instanceof PriceMap ? $priceMapId->id : $priceMapId);
    }
}
