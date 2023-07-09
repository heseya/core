<?php

namespace App\Rules;

use App\Models\AuthProvider;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\Rule;

class AuthProviderActive implements DataAwareRule, Rule
{
    protected array $data;

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     */
    public function passes($attribute, $value): bool
    {
        /** @var AuthProvider $provider */
        $provider = AuthProvider::query()->where('key', request()->route('authProviderKey'))->first();

        return (bool) (
            array_key_exists('client_id', $this->data) && array_key_exists('client_secret', $this->data)
            || $provider->client_id !== null && $provider->client_secret !== null
        );
    }

    public function message(): string
    {
        return 'Active field value cannot be true if client_id and client_secret fields are missing.';
    }

    public function setData($data): self|static
    {
        $this->data = $data;

        return $this;
    }
}
