<?php

namespace App\Rules;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Models\Address;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class OrganizationUniqueVat implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!isset($value['vat'])) {
            $fail(Exceptions::CLIENT_ORGANIZATION_VAT_REQUIRED->value);

            return;
        }

        if (
            Address::query()->where('vat', '=', $value['vat'])->whereHas('organizations')->exists()
        ) {
            $fail(Exceptions::CLIENT_ORGANIZATION_EXIST->value);
        }
    }
}
