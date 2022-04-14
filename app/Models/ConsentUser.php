<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @mixin IdeHelperConsentUser
 */
class ConsentUser extends Pivot
{
    protected $casts = [
        'value' => 'boolean',
    ];
}
