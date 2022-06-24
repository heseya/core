<?php

namespace App\Enums;

use App\Traits\EnumUtilities;

enum SchemaType: string
{
    use EnumUtilities;

    case STRING = 'string';
    case NUMERIC = 'numeric';
    case BOOLEAN = 'boolean';
    case DATE = 'date';
    case SELECT = 'select';
    case FILE = 'file';
    case MULTIPLY = 'multiply';
    case MULTIPLY_SCHEMA = 'multiply_schema';
}
