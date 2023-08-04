<?php

namespace App\Enums;

use App\Enums\Traits\EnumTrait;

enum AttributeType: string
{
    use EnumTrait;

    case SINGLE_OPTION = 'single-option';
    case MULTI_CHOICE_OPTION = 'multi-choice-option';
    case NUMBER = 'number';
    case DATE = 'date';
}
