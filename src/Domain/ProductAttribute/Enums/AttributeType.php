<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Enums;

use App\Enums\Traits\EnumTrait;

enum AttributeType: string
{
    use EnumTrait;

    case SINGLE_OPTION = AttributeTypeValues::SINGLE_OPTION;
    case MULTI_CHOICE_OPTION = AttributeTypeValues::MULTI_CHOICE_OPTION;
    case NUMBER = AttributeTypeValues::NUMBER;
    case DATE = AttributeTypeValues::DATE;
}
