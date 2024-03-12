<?php

namespace App\Enums;

use App\Enums\Traits\EnumTrait;

enum TFAType: string
{
    use EnumTrait;

    case APP = 'app';
    case EMAIL = 'email';
}
