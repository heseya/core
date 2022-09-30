<?php

namespace App\Enums;

use App\Traits\EnumUtilities;

enum MediaType: string
{
    use EnumUtilities;

    public const OTHER = 'other';
    public const PHOTO = 'photo';
    public const VIDEO = 'video';
    public const DOCUMENT = 'document';
}
