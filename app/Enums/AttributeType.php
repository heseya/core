<?php

namespace App\Enums;

enum AttributeType: string
{
    case SINGLE_OPTION = 'single-option';
    case MULTI_CHOICE_OPTION = 'multi-choice-option';
    case NUMBER = 'number';
    case DATE = 'date';
}
