<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @mixin IdeHelperMediaResponsiveMedia
 */
class MediaResponsiveMedia extends Pivot
{
    protected $fillable = [
        'min_screen_width',
    ];
}
