<?php

namespace App\Enums;

use App\Enums\Traits\EnumTrait;

enum TwitterCardType: string
{
    use EnumTrait;

    case SUMMARY = 'summary';
    case SUMMARY_LARGE_IMAGE = 'summary_large_image';
}
