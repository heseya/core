<?php

namespace Heseya\Data\Rules;

use Closure;
use Heseya\Data\Contracts\CoerceableEnum;
use Illuminate\Contracts\Validation\ValidationRule;

/** @param class-string<\BackedEnum>&CoerceableEnum $enumClass */
class EnumValueOrKey implements ValidationRule
{
    /** @param class-string<\BackedEnum>&CoerceableEnum $enumClass */
    public function __construct(
        protected string $enumClass
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (
            !(is_string($value) || is_int($value))
            || !enum_exists($this->enumClass)
            || !method_exists($this->enumClass, 'coerce')
            || $this->enumClass::coerce($value) === null
        ) {
            $fail(__('Enum :enum cannot be coerced from value ":key"', [
                'enum' => $this->enumClass,
                'key' => $value,
            ]));
        }
    }
}
