<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;

class AuthResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'token' => $this->accessToken,
            'expires_at' => $this->token->expires_at,
            'user' => UserResource::make(User::find($this->token->user_id)), // :/
            'scopes' => $this->token->scopes,
        ];
    }
}
