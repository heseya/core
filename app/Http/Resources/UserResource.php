<?php

namespace App\Http\Resources;

use App\Traits\MetadataResource;
use Illuminate\Http\Request;

class UserResource extends Resource
{
    use MetadataResource;

    public function base(Request $request): array
    {
        return array_merge([
            'id' => $this->getKey(),
            'email' => $this->email,
            'name' => $this->name,
            'avatar' => $this->avatar,
            'roles' => RoleResource::collection($this->roles),
            'is_tfa_active' => $this->is_tfa_active,
        ], $this->metadataResource('users.show_metadata_private'));
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
