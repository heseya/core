<?php

namespace App\Rules;

use BackedEnum;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** @param class-string<BackedEnum> $enumClass */
class EnumKey implements ValidationRule
{
    /**
     * @param class-string<BackedEnum> $enumClass
     */
    public function __construct(
        protected string $enumClass
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (
            !is_string($value)
            || !enum_exists($this->enumClass)
            || !method_exists($this->enumClass, 'tryFromName')
            || $this->enumClass::tryFromName($value) === null
        ) {
            $fail(__('Enum :enum has no key :key', [
                'enum' => $this->enumClass,
                'key' => $value,
            ]));
        }
    }
}
