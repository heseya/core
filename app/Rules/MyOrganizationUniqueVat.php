<?php

namespace App\Rules;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Models\Address;
use App\Models\User;
use Closure;
use Domain\Organization\Models\Organization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class MyOrganizationUniqueVat implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var Organization|null $organization */
        $organization = $user->organizations()->first();

        if (!$organization) {
            $fail(Exceptions::CLIENT_USER_NOT_IN_ORGANIZATION->value);

            return;
        }

        if (!isset($value['vat'])) {
            $fail(Exceptions::CLIENT_ORGANIZATION_VAT_REQUIRED->value);

            return;
        }

        if (
            Address::query()->where('vat', '=', $value['vat'])
                ->whereHas('organizations', fn (Builder $query) => $query->where('id', '!=', $organization->getKey()))
                ->exists()
        ) {
            $fail(Exceptions::CLIENT_ORGANIZATION_EXIST->value);
        }
    }
}
