<?php

namespace App\Enums;

use App\Enums\Traits\EnumTrait;

enum DiscountType: string
{
    use EnumTrait;

    case AMOUNT = 'amount';
    case PERCENTAGE = 'percentage';

    public function getPriority(): int
    {
        return match ($this) {
            self::AMOUNT => 0,
            self::PERCENTAGE => 1
        };
    }
}
