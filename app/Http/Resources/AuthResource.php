<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Services\Contracts\TokenServiceContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class AuthResource extends Resource
{
    private TokenServiceContract $tokenService;

    public function __construct($resource)
    {
        parent::__construct($resource);

        $this->tokenService = App::make(TokenServiceContract::class);
    }

    public function base(Request $request): array
    {
        $authenticable = $this->tokenService->getUser($this->token);

        return [
            'user' => $authenticable instanceof User ? UserResource::make($authenticable)
                : AppResource::make($authenticable),
            'token' => $this->token,
            'identity_token' => $this->identity_token,
            'refresh_token' => $this->refresh_token,
        ];
    }
}
