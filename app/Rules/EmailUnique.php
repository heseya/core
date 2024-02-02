<?php

namespace App\Rules;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EmailUnique implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (User::query()->where('email', '=', $value)->whereNull('deleted_at')->count() > 0) {
            $fail(Exceptions::CLIENT_EMAIL_TAKEN->value);
        }
    }
}
