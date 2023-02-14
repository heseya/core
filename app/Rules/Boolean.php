<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class Boolean implements Rule
{
    public function passes($attribute, $value): bool
    {
        return in_array($value, [true, false, 'true', 'false', 0, 1, '0', '1'], true);
    }

    public function message(): string
    {
        return 'The :attribute must be boolean.';
    }
}
