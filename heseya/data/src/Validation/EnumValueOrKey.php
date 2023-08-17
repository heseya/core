<?php

namespace Heseya\Data\Validation;

use Attribute;
use Heseya\Data\Rules\EnumValueOrKey as EnumValueOrKeyRule;
use Spatie\LaravelData\Attributes\Validation\ObjectValidationAttribute;
use Spatie\LaravelData\Support\Validation\References\RouteParameterReference;
use Spatie\LaravelData\Support\Validation\ValidationPath;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class EnumValueOrKey extends ObjectValidationAttribute
{
    protected EnumValueOrKeyRule $rule;

    public function __construct(string|EnumValueOrKeyRule|RouteParameterReference $enum)
    {
        $this->rule = $enum instanceof EnumValueOrKeyRule
            ? $enum
            : new EnumValueOrKeyRule((string)$enum);
    }

    public static function keyword(): string
    {
        return 'enum-value-or-key';
    }

    public function getRule(ValidationPath $path): object|string
    {
        return $this->rule;
    }

    public static function create(string ...$parameters): static
    {
        return new static(new EnumValueOrKeyRule($parameters[0]));
    }
}
