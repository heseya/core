<?php

namespace App\Enums;

use App\Enums\Traits\EnumTrait;
use Heseya\Data\Contracts\CoerceableEnum;

enum SchemaType: int implements CoerceableEnum
{
    use EnumTrait;

    case STRING = 0;
    case NUMERIC = 1;
    case BOOLEAN = 2;
    case DATE = 3;
    case SELECT = 4;
    case FILE = 5;
    case MULTIPLY = 6;
    case MULTIPLY_SCHEMA = 7;
}
