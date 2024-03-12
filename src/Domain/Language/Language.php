<?php

declare(strict_types=1);

namespace Domain\Language;

use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @mixin IdeHelperLanguage
 */
final class Language extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'iso',
        'name',
        'default',
        'hidden',
    ];

    protected $casts = [
        'default' => 'boolean',
        'hidden' => 'boolean',
    ];

    public static function default(): self|null
    {
        return self::where('default', '=', true)->first();
    }
}
