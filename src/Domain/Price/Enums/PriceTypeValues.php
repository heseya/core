<?php

declare(strict_types=1);

namespace Domain\Price\Enums;

abstract readonly class PriceTypeValues
{
    public const PRICE_BASE = 'price_base';
    public const PRICE_MIN = 'price_min';
    public const PRICE_MAX = 'price_max';
    public const PRICE_MIN_INITIAL = 'price_min_initial';
    public const PRICE_MAX_INITIAL = 'price_max_initial';
    public const PRICE_FOR_SCHEMA = 'schema';
    public const PRICE_FOR_OPTION = 'option';
}
