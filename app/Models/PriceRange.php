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
     *
     * @var array<string>
     */
    protected $fillable = [
        'start',
//
//        'value',
    ];

    ////////////////////////
    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start' => 'float',
    ];

    public function prices(): MorphMany
    {
        return $this->morphMany(Price::class, 'model');
    }
    ////////////////////
}
