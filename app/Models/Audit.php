<?php

namespace App\Models;

use App\Traits\HasUuid;
use OwenIt\Auditing\Models\Audit as BaseAudit;

/**
 * @mixin IdeHelperAudit
 */
class Audit extends BaseAudit
{
    use HasUuid;

    public const UPDATED_AT = null;
}
