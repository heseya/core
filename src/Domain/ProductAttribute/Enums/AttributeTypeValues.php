<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Enums;

abstract class AttributeTypeValues
{
    public const SINGLE_OPTION = 'single-option';
    public const MULTI_CHOICE_OPTION = 'multi-choice-option';
    public const NUMBER = 'number';
    public const DATE = 'date';
}
