<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class ShippingType extends Enum
{
    public const NONE = 'none';
    public const ADDRESS = 'address';
    public const POINT = 'point';
    public const POINT_EXTERNAL = 'point-external';
}
