<?php

namespace App\Enums;

use App\Enums\Traits\EnumTrait;

enum MediaType: string
{
    use EnumTrait;

    case OTHER = 'other';

    case DOCUMENT = 'document';
    case PHOTO = 'photo';
    case VIDEO = 'video';
}
