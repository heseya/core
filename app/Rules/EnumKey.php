<?php

namespace App\Rules;

use BenSampo\Enum\Rules\EnumKey as BenEnumKey;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Str;

class EnumKey extends BenEnumKey implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     */
    public function passes($attribute, $value): bool
    {
        return $this->enumClass::hasKey(Str::upper($value));
    }
}
