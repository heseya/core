<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class TokenType extends Enum
{
    public const ACCESS = 'access';
    public const IDENTITY = 'identity';
    public const REFRESH = 'refresh';
}
