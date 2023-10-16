<?php

namespace App\Rules;

use App\Enums\ExceptionsEnums\Exceptions;
use Closure;
use Domain\Organization\Models\OrganizationToken;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class OrganizationTokenEmail implements DataAwareRule, ValidationRule
{
    protected array $data;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (isset($this->data['organization_token'])) {
            $organizationToken = OrganizationToken::query()->where('token', '=', $this->data['organization_token'])->firstOrFail();

            if ($organizationToken->email !== $value) {
                $fail(Exceptions::CLIENT_ORGANIZATION_TOKEN_EMAIL->value);
            }
        }
    }

    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }
}
