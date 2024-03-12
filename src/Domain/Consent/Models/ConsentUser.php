<?php

declare(strict_types=1);

namespace Domain\Consent\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @mixin IdeHelperConsentUser
 */
final class ConsentUser extends Pivot
{
    protected $casts = [
        'value' => 'boolean',
    ];
}
