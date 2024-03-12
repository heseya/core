<?php

declare(strict_types=1);

namespace Domain\Banner\Models;

use App\Models\Interfaces\Translatable;
use App\Models\Media;
use App\Models\MediaBannerMedia;
use App\Models\Model;
use App\Traits\CustomHasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @mixin IdeHelperBannerMedia
 */
final class BannerMedia extends Model implements Translatable
{
    use CustomHasTranslations;
    use HasFactory;

    public const HIDDEN_PERMISSION = 'banners.show_hidden';

    protected $fillable = [
        'title',
        'subtitle',
        'url',
        'order',
        'published',
    ];

    /** @var string[] */
    protected array $translatable = [
        'title',
        'subtitle',
    ];

    protected $casts = [
        'published' => 'array',
    ];

    /**
     * @return BelongsToMany<Media>
     */
    public function media(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'media_responsive_media')
            ->with('metadata', 'metadataPrivate')
            ->withPivot('min_screen_width')
            ->using(MediaBannerMedia::class);
    }

    /**
     * @param Builder<self> $query
     *
     * @return Builder<self>
     */
    public function scopeReversed(Builder $query): Builder
    {
        return $query->withoutGlobalScope('ordered')
            ->orderBy('order', 'desc');
    }
}
