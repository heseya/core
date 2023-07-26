<?php

namespace App\Rules;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Models\Consent;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

readonly class RequiredConsents implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $consents = Consent::query()->where('required', true)->pluck('id');

        if (!$consents->every(
            fn ($consent) => array_key_exists($consent, $value) && $value[$consent]
        )) {
            $fail(Exceptions::CLIENT_NOT_ACCEPTED_ALL_REQUIRED_CONSENTS);
        }
    }
}
