<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\MediaStoreRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class AuthProviderDto extends Dto implements InstantiateFromRequest
{
    private string|null|Missing $client_id;
    private string|null|Missing $client_secret;
    private bool|null|Missing $active;

    public static function instantiateFromRequest(FormRequest|MediaStoreRequest $request): self
    {
        return new self(
            client_id: $request->input('client_id', new Missing()),
            client_secret: $request->input('client_secret', new Missing()),
            active: $request->input('active', new Missing()),
        );
    }

    public function getClientId(): string|Missing|null
    {
        return $this->client_id;
    }

    public function getClientSecret(): string|Missing|null
    {
        return $this->client_secret;
    }

    public function getActive(): bool|Missing|null
    {
        return $this->active;
    }
}
