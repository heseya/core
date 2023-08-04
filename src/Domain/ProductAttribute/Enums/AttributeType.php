<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Enums;

enum AttributeType: string
{
    case SINGLE_OPTION = 'single-option';
    case MULTI_CHOICE_OPTION = 'multi-choice-option';
    case NUMBER = 'number';
    case DATE = 'date';
}
