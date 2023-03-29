<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

class SavedAddressType extends Enum
{
    public const SHIPPING = 0;
    public const BILLING = 1;
}
