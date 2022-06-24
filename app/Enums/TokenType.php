<?php

namespace App\Enums;

use App\Traits\EnumUtilities;

enum TokenType: string
{
    use EnumUtilities;

    case ACCESS = 'access';
    case IDENTITY = 'identity';
    case REFRESH = 'refresh';
}
