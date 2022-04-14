<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class Boolean implements Rule
{
    public function passes($attribute, $value)
    {
        return is_bool($value);
    }

    public function message()
    {
        return 'The :attribute must be one of the following: true, false, on, off, yes, no, 1, 0.';
    }
}
