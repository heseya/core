<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Str;

class HttpsRule implements Rule
{
    public function passes($attribute, $value): bool
    {
        return Str::startsWith($value, 'https://');
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return ':attribute must starts with `https://`.';
    }
}
