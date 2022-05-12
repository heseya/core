<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @mixin IdeHelperBannerMedia
 */
class BannerMedia extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'subtitle',
        'url',
        'order',
    ];

    public function media(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'media_responsive_media')
            ->with('metadata', 'metadataPrivate')
            ->withPivot('min_screen_width')
            ->using(MediaBannerMedia::class);
    }

    public function scopeReversed(Builder $query): Builder
    {
        return $query->withoutGlobalScope('ordered')
            ->orderBy('order', 'desc');
    }
}
