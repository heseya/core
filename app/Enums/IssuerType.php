<?php

namespace App\Enums;

use App\Traits\EnumUtilities;

enum IssuerType: string
{
    use EnumUtilities;

    case APP = 'app';
    case USER = 'user';
    case UNAUTHENTICATED = 'unauthenticated';
}
