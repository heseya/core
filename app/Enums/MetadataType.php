<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

class MetadataType extends Enum
{
    public const STRING = 'string';
    public const NUMBER = 'number';
    public const BOOLEAN = 'boolean';

    public static function matchType(bool|int|float|string|null $value): string
    {
        return match (gettype($value)) {
            'boolean' => self::BOOLEAN,
            'integer', 'double' => self::NUMBER,
            default => self::STRING,
        };
    }
}
