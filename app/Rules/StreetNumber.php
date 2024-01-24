<?php

namespace App\Rules;

use App\Enums\ExceptionsEnums\Exceptions;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StreetNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!preg_match('/^[a-zA-Z0-9\p{L}\-\'.]+(?:\s[a-zA-Z1-9\p{L}\-\'.]+)*(?:\s[0-9]+[a-zA-Z]*(?:\/[0-9]+[a-zA-Z]*)*)+$/u', $value)) {
            $fail(Exceptions::CLIENT_STREET_NUMBER->value);
        }
    }
}
