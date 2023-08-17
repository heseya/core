<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Enums;

use App\Enums\Traits\EnumTrait;

enum AttributeType: string
{
    use EnumTrait;

    case SINGLE_OPTION = 'single-option';
    case MULTI_CHOICE_OPTION = 'multi-choice-option';
    case NUMBER = 'number';
    case DATE = 'date';

    public function getOptionFieldByType(): string
    {
        return match ($this) {
            self::SINGLE_OPTION, self::MULTI_CHOICE_OPTION => 'name',
            self::NUMBER => 'value_number',
            self::DATE => 'value_date',
        };
    }
}
