<?php

namespace App\Models;

use App\Traits\HasUuid;

class Audit extends \OwenIt\Auditing\Models\Audit
{
    use HasUuid;
}
