<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class SchemaType extends Enum
{
    public const STRING = 'string';
    public const NUMERIC = 'numeric';
    public const BOOLEAN = 'boolean';
    public const DATE = 'date';
    public const SELECT = 'select';
    public const FILE = 'file';
    public const MULTIPLY = 'multiply';
    public const MULTIPLY_SCHEMA = 'multiply_schema';
}
