<?php

namespace App\Http\Resources;

use App\Enums\RoleType;
use App\Traits\MetadataResource;
use Illuminate\Http\Request;

class RoleResource extends Resource
{
    use MetadataResource;

    public function base(Request $request): array
    {
        return array_merge([
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'is_registration_role' => $this->resource->is_registration_role,
            'assignable' => $this->resource->isAssignable(),
            'deletable' => $this->resource->type->is(RoleType::REGULAR),
            'users_count' => $this->resource->users_count,
            'is_joinable' => $this->resource->is_joinable,
        ], $this->metadataResource('roles.show_metadata_private'));
    }

    public function view(Request $request): array
    {
        return [
            'permissions' => $this->resource->getPermissionNames()->sort()->values(),
            'locked_permissions' => $this->resource->type->is(RoleType::OWNER),
        ];
    }
}
