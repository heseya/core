<?php

declare(strict_types=1);

namespace Domain\PriceMap;

use App\Models\Model;
use App\Models\Product;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\PriceMap\Resources\PriceMapProductPriceData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\LaravelData\WithData;

/**
 * @property Money $value
 *
 * @mixin IdeHelperPriceMapProductPrice
 */
final class PriceMapProductPrice extends Model
{
    use HasFactory;
    /**
     * @use WithData<PriceMapProductPriceData>
     */
    use WithData;

    protected string $dataClass = PriceMapProductPriceData::class;

    protected $fillable = [
        'currency',
        'is_net',
        'price_map_id',
        'product_id',
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
     * @return BelongsTo<Product,self>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
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
        $query = $query->where('price_map_id', $priceMapId instanceof PriceMap ? $priceMapId->id : $priceMapId);
        if ($priceMapId instanceof PriceMap) {
            $query = $query->where('currency', $priceMapId->currency->value);
        }

        return $query;
    }
}
