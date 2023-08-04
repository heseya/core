<?php

namespace App\Enums;

use App\Enums\Traits\EnumTrait;

enum SavedAddressType: int
{
    use EnumTrait;

    case SHIPPING = 0;
    case BILLING = 1;
}
