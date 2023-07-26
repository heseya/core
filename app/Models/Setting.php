<?php

namespace App\Models;

use Illuminate\Support\Facades\Config;

/**
 * @mixin IdeHelperSetting
 */
class Setting extends Model
{
    protected $fillable = [
        'name',
        'value',
        'public',
    ];

    protected $casts = [
        'public' => 'bool',
    ];

    public function getPermanentAttribute(): bool
    {
        return Config::get('settings.' . $this->name) !== null;
    }
}
