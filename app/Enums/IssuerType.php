<?php

namespace App\Enums;

use App\Enums\Traits\EnumTrait;

enum IssuerType: string
{
    use EnumTrait;

    case APP = 'app';
    case USER = 'user';
    case UNAUTHENTICATED = 'unauthenticated';
}
