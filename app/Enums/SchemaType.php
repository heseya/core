<?php

namespace App\Enums;

use App\Enums\Traits\EnumTrait;
use Heseya\Data\Contracts\CoerceableEnum;


enum SchemaType: int implements CoerceableEnum
{
    use EnumTrait;

    /**
     * @deprecated
     */
    case STRING = 0;

    /**
     * @deprecated
     */
    case NUMERIC = 1;

    /**
     * @deprecated
     */
    case BOOLEAN = 2;

    /**
     * @deprecated
     */
    case DATE = 3;

    case SELECT = 4;

    /**
     * @deprecated
     */
    case FILE = 5;

    /**
     * @deprecated
     */
    case MULTIPLY = 6;

    /**
     * @deprecated
     */
    case MULTIPLY_SCHEMA = 7;
}
