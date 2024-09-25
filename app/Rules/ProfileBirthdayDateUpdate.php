<?php

namespace App\Rules;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Models\App;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

class ProfileBirthdayDateUpdate implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        /** @var User|App $user */
        $user = Auth::user();

        if ($user->hasPermissionTo('users.edit')) {
            return;
        }

        if ($user instanceof User && $user->birthday_date && $user->birthday_date !== $value) {
            $fail(Exceptions::CLIENT_PROFILE_BIRTHDAY_UPDATE->value);
        }
    }
}
