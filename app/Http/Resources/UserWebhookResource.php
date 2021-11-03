<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class UserWebhookResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'email' => $this->email,
            'name' => $this->name,
            'avatar' => $this->avatar,
            'roles' => RoleWebhookResource::collection($this->roles),
        ];
    }
}
