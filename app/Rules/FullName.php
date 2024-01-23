<?php

namespace App\Rules;

use App\Enums\ExceptionsEnums\Exceptions;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class FullName implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!preg_match('/^[a-zA-Z1-9\p{L}\-\']{2,}(?:\s[a-zA-Z1-9\p{L}\-\']{2,})+$/u', $value)) {
            $fail(Exceptions::CLIENT_FULL_NAME->value);
        }
    }
}
