<?php

declare(strict_types=1);

namespace Domain\Currency;

enum Currency: string
{
    public const DEFAULT = self::PLN;

    case PLN = 'PLN';
    case EUR = 'EUR';
}
