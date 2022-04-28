<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @mixin IdeHelperMediaBannerMedia
 */
class MediaBannerMedia extends Pivot
{
    protected $fillable = [
        'min_screen_width',
    ];
}
