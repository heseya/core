<?php

namespace App\Models;

use App\Models\Interfaces\Translatable;
use App\Traits\CustomHasTranslations;
use App\Traits\HasMetadata;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\PriceMap\PriceMapSchemaOptionPrice;
use Domain\ProductSchema\Models\Schema;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

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
        'default',
    ];

    protected array $translatable = [
        'name',
    ];

    protected $casts = [
        'available' => 'bool',
        'default' => 'bool',
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

    /**
     * @deprecated
     */
    public function getMappedPriceForCurrency(Currency $currency): PriceMapSchemaOptionPrice
    {
        return $this->relationLoaded('mapPrices')
            ? $this->mapPrices->where('currency', $currency->value)->firstOrFail()
            : $this->mapPrices()->where('currency', $currency->value)->firstOrFail();
    }

    /**
     * @deprecated
     */
    public function getPriceForCurrency(Currency $currency): Money
    {
        return $this->getMappedPriceForCurrency($currency)->value;
    }
}
