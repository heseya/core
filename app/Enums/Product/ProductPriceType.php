<?php

namespace App\Enums\Product;

enum ProductPriceType: string
{
    case PRICE_BASE = 'price_base';
    case PRICE_MIN = 'price_min';
    case PRICE_MAX = 'price_max';
    case PRICE_MIN_INITIAL = 'price_min_initial';
    case PRICE_MAX_INITIAL = 'price_max_initial';
}
