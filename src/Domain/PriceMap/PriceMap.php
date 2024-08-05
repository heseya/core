<?php

declare(strict_types=1);

namespace Domain\PriceMap;

use App\Models\Model;
use Domain\Currency\Currency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperPriceMap
 */
final class PriceMap extends Model
{
    use HasFactory;

    protected $fillable = [
        'currency',
        'description',
        'is_net',
        'name',
    ];

    protected $casts = [
        'currency' => Currency::class,
        'is_net' => 'bool',
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
}
