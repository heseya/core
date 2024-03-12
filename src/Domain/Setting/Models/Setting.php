<?php

declare(strict_types=1);

namespace Domain\Setting\Models;

use App\Models\IdeHelperSetting;
use App\Models\Model;
use Illuminate\Support\Facades\Config;

/**
 * @mixin IdeHelperSetting
 */
final class Setting extends Model
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
