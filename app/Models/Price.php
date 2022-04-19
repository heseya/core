<?php

namespace App\Models;

/**
 * @mixin IdeHelperPrice
 */
class Price extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'value',
        'model_id',
        'model_type',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'value' => 'float',
    ];
}
