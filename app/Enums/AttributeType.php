<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class AttributeType extends Enum
{
    public const SINGLE_OPTION = 'single-option';
    public const NUMBER = 'number';
    public const DATE = 'date';
}
