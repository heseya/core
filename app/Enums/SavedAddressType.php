<?php

namespace App\Enums;

use App\Traits\EnumUtilities;

enum SavedAddressType: string
{
    use EnumUtilities;

    case DELIVERY = 'delivery';
    case INVOICE = 'invoice';
}
