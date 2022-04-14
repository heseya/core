<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @mixin IdeHelperResponsiveMedia
 */
class ResponsiveMedia extends Model
{
    use HasFactory;

    protected $fillable = [
        'order',
    ];

    public function media(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'media_responsive_media')
            ->withPivot('min_screen_width')
            ->using(MediaResponsiveMedia::class);
    }
}
