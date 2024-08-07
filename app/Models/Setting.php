<?php

namespace App\Models;

use Illuminate\Support\Facades\Config;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @mixin IdeHelperSetting
 */
class Setting extends Model implements AuditableContract
{
    use Auditable;

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
