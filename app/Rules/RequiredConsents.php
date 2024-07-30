<?php

namespace App\Rules;

use App\Enums\ExceptionsEnums\Exceptions;
use Closure;
use Domain\Consent\Enums\ConsentType;
use Domain\Consent\Models\Consent;
use Illuminate\Contracts\Validation\ValidationRule;

readonly class RequiredConsents implements ValidationRule
{
    public function __construct(private ConsentType $type) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $consents = Consent::query()
            ->where('type', '=', $this->type)
            ->where('required', true)
            ->pluck('id');

        if (!$consents->every(
            fn ($consent) => array_key_exists($consent, $value) && $value[$consent],
        )) {
            $fail(Exceptions::CLIENT_NOT_ACCEPTED_ALL_REQUIRED_CONSENTS->value);
        }
    }
}
