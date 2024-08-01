<?php

namespace App\Rules;

use App\Enums\ExceptionsEnums\Exceptions;
use Closure;
use Domain\Consent\Enums\ConsentType;
use Domain\Consent\Models\Consent;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class ConsentsExists implements ValidationRule
{
    public function __construct(
        private ConsentType $type,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!Consent::query()->where('type', '=', $this->type)->where('id', Str::after($attribute, 'consents.'))->exists()) {
            $fail(Exceptions::CLIENT_CONSENT_NOT_EXISTS->value);
        }
    }
}
