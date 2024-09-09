<?php

namespace App\Models;

use App\Models\Interfaces\Translatable;
use App\Traits\CustomHasTranslations;
use App\Traits\HasMetadata;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\PriceMap\PriceMap;
use Domain\PriceMap\PriceMapSchemaOptionPrice;
use Domain\ProductSchema\Models\Schema;
use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

/**
 * @mixin IdeHelperOption
 */
class Option extends Model implements Translatable
{
    use CustomHasTranslations;
    use HasFactory;
    use HasMetadata;

    public const HIDDEN_PERMISSION = 'options.show_hidden';

    protected $fillable = [
        'name',
        'schema_id',
        'order',
        'available',
        'shipping_time',
        'shipping_date',
    ];

    protected array $translatable = [
        'name',
    ];

    protected $casts = [
        'available' => 'bool',
    ];

    public function items(): BelongsToMany
    {
        return $this
            ->belongsToMany(Item::class, 'option_items')
            ->withPivot('required_quantity');
    }

    public function schema(): BelongsTo
    {
        return $this->belongsTo(Schema::class);
    }

    /**
     * @deprecated
     */
    public function prices(): MorphMany
    {
        return $this->morphMany(Price::class, 'model');
    }

    public function mapPrices(): HasMany
    {
        return $this->hasMany(PriceMapSchemaOptionPrice::class);
    }

    public function getMappedPriceForCurrency(Currency $currency): PriceMapSchemaOptionPrice
    {
        return $this->relationLoaded('mapPrices')
            ? $this->mapPrices->where('currency', $currency->value)->firstOrFail()
            : $this->mapPrices()->where('currency', $currency->value)->firstOrFail();
    }

    public function getPriceForCurrency(Currency $currency): Money
    {
        return $this->getMappedPriceForCurrency($currency)->value;
    }

    public function getMappedPriceForPriceMap(PriceMap|string $priceMap): PriceMapSchemaOptionPrice
    {
        /** @var Builder<PriceMapSchemaOptionPrice>|Collection<int,PriceMapSchemaOptionPrice> $price */
        $price = $this->relationLoaded('mapPrices')
            ? $this->mapPrices->where('price_map_id', $priceMap instanceof PriceMap ? $priceMap->id : $priceMap)
            : $this->mapPrices()->where('price_map_id', $priceMap instanceof PriceMap ? $priceMap->id : $priceMap);

        if ($priceMap instanceof PriceMap) {
            $price = $price->where('currency', '=', $priceMap->currency->value);
        }

        return $price->firstOrFail();
    }

    public function getPriceForPriceMap(PriceMap|string $priceMap): Money
    {
        return $this->getMappedPriceForPriceMap($priceMap)->value;
    }

    public function getMappedPriceForSalesChannel(SalesChannel|string $salesChannel): PriceMapSchemaOptionPrice
    {
        $salesChannel = $salesChannel instanceof SalesChannel ? $salesChannel : SalesChannel::findOrFail($salesChannel);
        assert($salesChannel instanceof SalesChannel);
        $priceMap = $salesChannel->priceMap;
        assert($priceMap instanceof PriceMap);

        return $this->getMappedPriceForPriceMap($priceMap);
    }
}
