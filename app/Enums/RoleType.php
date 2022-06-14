<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class RoleType extends Enum
{
    public const REGULAR = 'regular';
    public const OWNER = 'owner';
    public const UNAUTHENTICATED = 'unauthenticated';
    public const AUTHENTICATED = 'authenticated';
}
