<?php

namespace App\Enums;

use BenSampo\Enum\Enum;
use Illuminate\Support\Str;

class SchemaType extends Enum
{
    public const STRING = 0;
    public const NUMERIC = 1;
    public const BOOLEAN = 2;
    public const DATE = 3;
    public const SELECT = 4;
    public const FILE = 5;
    public const MULTIPLY = 6;
    public const MULTIPLY_SCHEMA = 7;

    public static function getKeys(): array
    {
        return array_map(
            fn (string $key) => Str::lower($key),
            array_keys(static::getConstants()),
        );
    }

    public static function getKey($value): string
    {
        return Str::lower(
            array_search($value, static::getConstants(), true),
        );
    }

    public static function getValue(string $key)
    {
        return static::getConstants()[Str::upper($key)];
    }
}
