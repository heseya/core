<?php

namespace App\Enums\Product;

use App\Enums\Traits\EnumTrait;

enum ProductPriceType: string
{
    use EnumTrait;

    case PRICE_BASE = 'price_base';
    case PRICE_MIN = 'price_min';
    case PRICE_MAX = 'price_max';
    case PRICE_MIN_INITIAL = 'price_min_initial';
    case PRICE_MAX_INITIAL = 'price_max_initial';
}
