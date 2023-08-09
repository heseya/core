<?php

namespace App\Enums;

use App\Enums\Traits\EnumTrait;

enum VisibilityType: string
{
    use EnumTrait;

    case PUBLIC = 'public';
    case PRIVATE = 'private';

    /*
     * TODO: Implement this visibility type.
     * This visibility type makes resource visible with specific request param.
     */
    //    case HIDDEN = 'hidden';
}
