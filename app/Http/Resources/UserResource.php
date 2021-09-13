<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class UserResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'email' => $this->email,
            'name' => $this->name,
            'avatar' => $this->avatar,
            'roles' => RoleResource::collection($this->roles),
        ];
    }

    public function view(Request $request): array
    {
        return [
            'permissions' => $this->getAllPermissions()
                ->map(fn ($perm) => $perm->name)
                ->sort()
                ->values(),
        ];
    }
}
