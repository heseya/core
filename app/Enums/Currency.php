<?php

declare(strict_types=1);

namespace App\Enums;

enum Currency: string
{
    public const DEFAULT = self::PLN;

    case PLN = 'PLN';
    case EUR = 'EUR';
}
