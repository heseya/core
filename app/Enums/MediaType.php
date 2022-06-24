<?php

namespace App\Enums;

use App\Traits\EnumUtilities;

enum MediaType: string
{
    use EnumUtilities;

    case OTHER = 'other';
    case PHOTO = 'photo';
    case VIDEO = 'video';
}
