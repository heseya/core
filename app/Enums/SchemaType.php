<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

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
}
