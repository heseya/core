<?php

namespace App\Enums;

use App\Traits\EnumUtilities;

enum MetadataType: string
{
    use EnumUtilities;

    case STRING = 'string';
    case NUMBER = 'number';
    case BOOLEAN = 'boolean';

    public static function matchType(bool|int|float|string|null $value): string
    {
        return match (gettype($value)) {
            'boolean' => self::BOOLEAN->value,
            'integer', 'double' => self::NUMBER->value,
            default => self::STRING->value,
        };
    }
}
