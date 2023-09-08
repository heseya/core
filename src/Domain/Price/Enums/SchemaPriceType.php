<?php

declare(strict_types=1);

namespace Domain\Price\Enums;

use App\Enums\Traits\EnumTrait;

enum SchemaPriceType: string
{
    use EnumTrait;

    case PRICE_BASE = PriceTypeValues::PRICE_FOR_SCHEMA;
}