<?php

declare(strict_types=1);

namespace Domain\Language;

use App\Models\Model;
use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /**
     * @return HasMany<SalesChannel>
     */
    public function salesChannels(): HasMany
    {
        return $this->hasMany(SalesChannel::class);
    }
}
