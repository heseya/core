<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class IssuerType extends Enum
{
    public const APP = 'app';
    public const USER = 'user';
    public const UNAUTHENTICATED = 'unauthenticated';
}
