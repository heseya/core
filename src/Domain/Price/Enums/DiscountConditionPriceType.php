<?php

declare(strict_types=1);

namespace Domain\Price\Enums;

use App\Enums\Traits\EnumTrait;

enum DiscountConditionPriceType: string
{
    use EnumTrait;

    case PRICE_MIN = PriceTypeValues::PRICE_MIN;
    case PRICE_MAX = PriceTypeValues::PRICE_MAX;
}
