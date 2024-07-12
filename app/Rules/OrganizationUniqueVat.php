<?php

namespace App\Rules;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Models\Address;
use Closure;
use Domain\Organization\Models\Organization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;

class OrganizationUniqueVat implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!isset($value['vat'])) {
            $fail(Exceptions::CLIENT_ORGANIZATION_VAT_REQUIRED->value);

            return;
        }
        /** @var Organization|null $organization */
        $organization = request()->route('organization');

        if (
            Address::query()->where('vat', '=', $value['vat'])->whereHas('organizations', function (Builder $query) use ($organization) {
                if ($organization) {
                    return $query->where('id', '!=', $organization->getKey());
                }

                return $query;
            })->exists()
        ) {
            $fail(Exceptions::CLIENT_ORGANIZATION_EXIST->value);
        }
    }
}
