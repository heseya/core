<?php

namespace App\Models;

/**
 * @mixin IdeHelperPrice
 */
class Price extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'value',
        'model_id',
        'model_type',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'value' => 'float',
    ];
}
