<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

class SchemaType extends Enum
{
    public const string = 0;
    public const numeric = 1;
    public const boolean = 2;
    public const date = 3;
    public const select = 4;
    public const file = 5;
    public const multiply = 6;
    public const multiply_schema = 7;
}
