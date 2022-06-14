<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

class SavedAddressType extends Enum
{
    public const DELIVERY = 'delivery';
    public const INVOICE = 'invoice';
}
