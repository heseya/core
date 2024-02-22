<?php

namespace App\Http\Resources;

use App\Models\User;
use Domain\Auth\Services\TokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class AuthResource extends Resource
{
    private TokenService $tokenService;

    public function __construct($resource)
    {
        parent::__construct($resource);

        $this->tokenService = App::make(TokenService::class);
    }

    public function base(Request $request): array
    {
        $authenticable = $this->tokenService->getUser($this->resource->token);

        return [
            'token' => $this->resource->token,
            'identity_token' => $this->resource->identity_token,
            'refresh_token' => $this->resource->refresh_token,
            'user' => $authenticable instanceof User ? UserWithSavedAddressesResource::make($authenticable)
                : AppWithSavedAddressesResource::make($authenticable),
        ];
    }
}
