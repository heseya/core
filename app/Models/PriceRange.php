<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @mixin IdeHelperPriceRange
 */
class PriceRange extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'start',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'start' => 'float',
    ];

    public function prices(): MorphMany
    {
        return $this->morphMany(Price::class, 'model');
    }
}
