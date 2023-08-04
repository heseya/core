<?php

namespace App\Rules;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\RoleType;
use App\Models\Role;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final readonly class IsRegistrationRole implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (
            !Role::query()
                ->where('id', '=', $value)
                ->where('is_registration_role', '=', true)
                ->where('type', '=', RoleType::REGULAR->value)
                ->exists()
        ) {
            $fail(Exceptions::CLIENT_REGISTER_WITH_NON_REGISTRATION_ROLE->value);
        }
    }
}
