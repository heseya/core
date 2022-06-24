<?php

namespace App\Enums;

use App\Traits\EnumUtilities;

enum RoleType: string
{
    use EnumUtilities;

    case REGULAR = 'regular';
    case OWNER = 'owner';
    case UNAUTHENTICATED = 'unauthenticated';
    case AUTHENTICATED = 'authenticated';
}
