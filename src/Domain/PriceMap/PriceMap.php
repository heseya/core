<?php

namespace Domain\PriceMap;

use App\Models\Model;
use Domain\Currency\Currency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\LaravelData\WithData;

/**
 * @mixin IdeHelperPriceMap
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
