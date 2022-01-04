<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperLanguage
 */
class Language extends Model
{
    use HasUuid, HasFactory;

    protected $fillable = [
        'iso',
        'name',
        'default',
        'hidden',
    ];

    protected $casts = [
        'default' => 'boolean',
        'hidden' => 'boolean',
    ];

    public static function default(): self
    {
        return self::where('default', true)->first();
    }
}
