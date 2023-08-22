<?php

namespace App\Models;

use App\Models\Interfaces\Translatable;
use App\Traits\CustomHasTranslations;
use App\Traits\HasMetadata;
use Brick\Money\Money;
use Database\Factories\OptionFactory;
use Domain\Currency\Currency;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property Collection<int, Price> $prices
 *
 * @method static OptionFactory factory()
 *
 * @mixin IdeHelperOption
 */
class Option extends Model implements Translatable
{
    use CustomHasTranslations;
    use HasFactory;
    use HasMetadata;

    protected const HIDDEN_PERMISSION = 'options.show_hidden';

    protected $fillable = [
        'name',
        'disabled',
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
        'disabled' => 'bool',
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

    public function prices(): MorphMany
    {
        return $this->morphMany(Price::class, 'model');
    }

    public function getPriceForCurrency(Currency $currency): Money
    {
        return $this->prices->where('currency', $currency->value)->firstOrFail()->value;
    }
}
