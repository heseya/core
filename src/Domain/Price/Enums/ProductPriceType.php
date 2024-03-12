<?php

declare(strict_types=1);

namespace Domain\Price\Enums;

use App\Enums\Traits\EnumTrait;

enum ProductPriceType: string
{
    use EnumTrait;

    case PRICE_BASE = PriceTypeValues::PRICE_BASE;
    case PRICE_MIN = PriceTypeValues::PRICE_MIN;
    case PRICE_MAX = PriceTypeValues::PRICE_MAX;
    case PRICE_MIN_INITIAL = PriceTypeValues::PRICE_MIN_INITIAL;
    case PRICE_MAX_INITIAL = PriceTypeValues::PRICE_MAX_INITIAL;
}
