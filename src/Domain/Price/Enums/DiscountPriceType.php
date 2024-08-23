<?php

declare(strict_types=1);

namespace Domain\Price\Enums;

use App\Enums\Traits\EnumTrait;

enum DiscountPriceType: string
{
    use EnumTrait;

    case AMOUNT = PriceTypeValues::AMOUNT_FOR_DISCOUNT;
}
