<?php

namespace App\Models;

use Brick\Money\Money;
use Database\Factories\PriceFactory;
use Domain\Currency\Currency;
use Domain\Price\Dtos\PriceDto;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\LaravelData\WithData;

/**

 *
 * @mixin IdeHelperPrice
 */
class PriceMap extends Model
{
    use HasFactory;
    use WithData;

    protected $fillable = [
        'name',
        'description',
        'currency',
        'is_net',
    ];

    protected $casts = [
        'is_net' => 'bool',
        'currency' => Currency::class,
    ];

    public function prices(): MorphMany
    {
        return $this->morphMany(PriceMapPrice::class, 'model');
    }
}
