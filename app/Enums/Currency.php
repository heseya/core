<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\EnumTrait;

enum Currency: string
{
    use EnumTrait;

    public const DEFAULT = self::PLN;

    case PLN = 'PLN';
    case EUR = 'EUR';
}
