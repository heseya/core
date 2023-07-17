<?php

namespace App\Rules;

use App\Enums\RoleType;
use App\Models\Role;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IsRegistrationRole implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (
            !Role::query()
                ->where('id', '=', $value)
                ->where('is_registration_role', '=', true)
                ->where('type', '=', RoleType::REGULAR)
                ->exists()
        ) {
            $fail("Cannot register with a role ID: {$value}");
        }
    }
}
