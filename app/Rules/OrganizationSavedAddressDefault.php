<?php

namespace App\Rules;

use App\Enums\ExceptionsEnums\Exceptions;
use Closure;
use Domain\Organization\Models\OrganizationSavedAddress;
use Illuminate\Contracts\Validation\ValidationRule;

class OrganizationSavedAddressDefault implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        /** @var OrganizationSavedAddress|null $address */
        $address = request()->route('delivery_address');

        if ($address && $address->default && !$value) {
            $fail(Exceptions::CLIENT_ORGANIZATION_ADDRESS_DEFAULT->value);
        }
    }
}
