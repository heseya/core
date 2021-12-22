<?php

namespace App\Enums;

use App\Models\Page;
use App\Models\Product;
use App\Models\ProductSet;
use BenSampo\Enum\Enum;

final class SeoModelType extends Enum
{
    public const PRODUCT = Product::class;
    public const PRODUCT_SET = ProductSet::class;
    public const PAGE = Page::class;
}
