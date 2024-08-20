<?php

declare(strict_types=1);

namespace Domain\Price\Enums;

use App\Enums\Traits\EnumTrait;

/**
 * @deprecated
 */
enum SchemaPriceType: string
{
    use EnumTrait;

    /**
     * @deprecated
     */
    case PRICE_BASE = PriceTypeValues::PRICE_FOR_SCHEMA;
}
