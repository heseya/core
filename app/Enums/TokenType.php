<?php

namespace App\Enums;

use App\Enums\Traits\EnumTrait;

enum TokenType: string
{
    use EnumTrait;

    case ACCESS = 'access';
    case IDENTITY = 'identity';
    case REFRESH = 'refresh';
}
