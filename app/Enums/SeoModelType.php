<?php

namespace App\Enums;

use App\Traits\EnumUtilities;

enum SeoModelType: string
{
    use EnumUtilities;

    case PRODUCT = 'App\Models\Product';
    case PRODUCT_SET = 'App\Models\ProductSet';
    case PAGE = 'App\Models\Page';
}
