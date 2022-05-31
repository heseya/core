<?php

namespace App\Http\Resources;

use App\Enums\RoleType;
use App\Models\Role;
use App\Traits\MetadataResource;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class UserResource extends Resource
{
    use MetadataResource;

    public function base(Request $request): array
    {
        /** @var Collection $roles */
        $roles = $this->resource->roles;

        $filtered = $roles->filter(function (Role $role) {
            return $role->type->value !== RoleType::AUTHENTICATED;
        });

        return array_merge([
            'id' => $this->resource->getKey(),
            'email' => $this->resource->email,
            'name' => $this->resource->name,
            'avatar' => $this->resource->avatar,
            'roles' => RoleResource::collection($filtered),
            'is_tfa_active' => $this->resource->is_tfa_active,
            'consents' => ConsentUserResource::collection($this->resource->consents),
        ], $this->metadataResource('users.show_metadata_private'));
    }

    public function view(Request $request): array
    {
        return [
            'permissions' => $this->resource->getAllPermissions()
                ->map(fn ($perm) => $perm->name)
                ->sort()
                ->values(),
        ];
    }
}
