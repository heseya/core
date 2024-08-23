<?php

declare(strict_types=1);

namespace Domain\PriceMap;

use App\Models\Model;
use Domain\Currency\Currency;
use Domain\PriceMap\Resources\PriceMapData;
use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\LaravelData\WithData;

/**
 * @mixin IdeHelperPriceMap
 */
final class PriceMap extends Model
{
    use HasFactory;
    /**
     * @use WithData<PriceMapData>
     */
    use WithData;

    protected string $dataClass = PriceMapData::class;

    protected $fillable = [
        'currency',
        'description',
        'is_net',
        'name',
    ];

    protected $casts = [
        'currency' => Currency::class,
        'is_net' => 'bool',
        'prices_generated' => 'bool',
    ];

    /**
     * @return HasMany<PriceMapProductPrice>
     */
    public function productPrices(): HasMany
    {
        return $this->hasMany(PriceMapProductPrice::class, 'price_map_id');
    }

    /**
     * @return HasMany<PriceMapSchemaOptionPrice>
     */
    public function schemaOptionsPrices(): HasMany
    {
        return $this->hasMany(PriceMapSchemaOptionPrice::class, 'price_map_id');
    }

    /**
     * @return HasMany<SalesChannel>
     */
    public function salesChannel(): HasMany
    {
        return $this->hasMany(SalesChannel::class);
    }
}
