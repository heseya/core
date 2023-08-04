<?php

namespace App\Enums;

use App\Enums\Traits\EnumTrait;

enum MetadataType: string
{
    use EnumTrait;

    case BOOLEAN = 'boolean';
    case NUMBER = 'number';
    case STRING = 'string';

    public static function matchType(bool|float|int|string|null $value): self
    {
        return match (true) {
            is_bool($value) => self::BOOLEAN,
            is_int($value), is_float($value) => self::NUMBER,
            default => self::STRING,
        };
    }
}
