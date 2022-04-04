<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class AttributeType extends Enum
{
    public const SINGLE_OPTION = 'single-option';
    public const MULTI_CHOICE_OPTION = 'multi-choice-option';
    public const NUMBER = 'number';
    public const DATE = 'date';
}
