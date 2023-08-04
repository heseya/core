<?php

namespace App\Enums;

use App\Enums\Traits\EnumTrait;

enum ShippingType: string
{
    use EnumTrait;

    case DIGITAL = 'digital';
    case ADDRESS = 'address';
    case POINT = 'point';
    case POINT_EXTERNAL = 'point-external';
}
