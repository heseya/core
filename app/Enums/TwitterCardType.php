<?php

namespace App\Enums;

use App\Traits\EnumUtilities;

enum TwitterCardType: string
{
    use EnumUtilities;

    case SUMMARY = 'summary';
    case SUMMARY_LARGE_IMAGE = 'summary_large_image';
}
