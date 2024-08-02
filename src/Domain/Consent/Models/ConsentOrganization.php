<?php

declare(strict_types=1);

namespace Domain\Consent\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @mixin IdeHelperConsentOrganization
 */
final class ConsentOrganization extends Pivot
{
    protected $casts = [
        'value' => 'boolean',
    ];
}
