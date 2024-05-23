<?php

namespace App\Rules;

use App\Enums\ExceptionsEnums\Exceptions;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Config;

class FullName implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (Config::get('flags.validate_address_fullname') && !preg_match('/^[a-zA-Z\p{L}\-\']{2,}(?:\s[a-zA-Z\p{L}\-\']{2,})+$/u', $value)) {
            $fail(Exceptions::CLIENT_FULL_NAME->value);
        }
    }
}
