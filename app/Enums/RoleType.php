<?php

namespace App\Enums;

use App\Enums\Traits\EnumTrait;

enum RoleType: int
{
    use EnumTrait;

    case REGULAR = 0;
    case OWNER = 1;
    case UNAUTHENTICATED = 2;
    case AUTHENTICATED = 3;
}
