<?php

namespace App\Enums;

use App\Traits\EnumUtilities;
use Exception;

enum DiscountType: string
{
    use EnumUtilities;

    case PERCENTAGE = 'percentage';
    case AMOUNT = 'amount';

    public static function getPriority(string $value): int
    {
        return match ($value) {
            self::AMOUNT->value => 0,
            self::PERCENTAGE->value => 1,
            default => throw new Exception('Unknown discount type'),
        };
    }
}
