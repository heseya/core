<?php

namespace App\Rules;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Models\User;
use Closure;
use Domain\Organization\Models\Organization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

class OrderOrganizationRequired implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        /** @var User $user */
        $user = Auth::user();

        if ($value === null) {
            if ($user->organizations->first()) {
                $fail(Exceptions::CLIENT_USER_IN_ORGANIZATION->value);

                return;
            }
        }

        if ($user->organizations->first()?->getKey() !== $value) {
            $fail(Exceptions::CLIENT_USER_IN_DIFFERENT_ORGANIZATION->value);

            return;
        }

        /** @var Organization $organization */
        $organization = Organization::query()->where('id', '=', $value)->firstOrFail();

        if (!$organization->is_complete) {
            $fail(Exceptions::CLIENT_ORGANIZATION_INACTIVE->value);

            return;
        }
    }
}
