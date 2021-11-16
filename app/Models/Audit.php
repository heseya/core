<?php

namespace App\Models;

use App\Traits\HasUuid;

/**
 * @mixin IdeHelperAudit
 */
class Audit extends \OwenIt\Auditing\Models\Audit
{
    use HasUuid;

    public const UPDATED_AT = null;
    protected $connection = 'mysql_audits';
}
