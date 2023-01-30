<?php

namespace App\Enums;

use BenSampo\Enum\Enum;
use Exception;

final class DiscountType extends Enum
{
    public const PERCENTAGE = 'percentage';
    public const AMOUNT = 'amount';

    public static function getPriority(string $value): int
    {
        return match ($value) {
            self::AMOUNT => 0,
            self::PERCENTAGE => 1,
            default => throw new Exception('Unknown discount type'),
        };
    }
}
