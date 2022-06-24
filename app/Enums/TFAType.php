<?php

namespace App\Enums;

use App\Traits\EnumUtilities;

enum TFAType: string
{
    use EnumUtilities;

    case APP = 'app';
    case EMAIL = 'email';
}
