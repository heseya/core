<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

class RoleType extends Enum
{
    public const REGULAR = 0;
    public const OWNER = 1;
    public const UNAUTHENTICATED = 2;
}
