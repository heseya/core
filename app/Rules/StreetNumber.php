<?php

namespace App\Rules;

use App\Enums\ExceptionsEnums\Exceptions;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StreetNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!preg_match('/\d/', $value)) {
            $fail(Exceptions::CLIENT_STREET_NUMBER->value);
        }
    }
}
