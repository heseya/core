<?php

namespace App\Enums;

use App\Enums\Traits\EnumTrait;

enum MediaSource: string
{
    use EnumTrait;

    case EXTERNAL = 'external';
    case SILVERBOX = 'silverbox';
}
