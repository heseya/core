<?php

namespace App\Enums;

use App\Traits\EnumUtilities;

enum AttributeType: string
{
    use EnumUtilities;

    case SINGLE_OPTION = 'single-option';
    case MULTI_CHOICE_OPTION = 'multi-choice-option';
    case NUMBER = 'number';
    case DATE = 'date';
}
