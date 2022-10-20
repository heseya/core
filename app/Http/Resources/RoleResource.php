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
            'assignable' => $this->resource->isAssignable(),
            'deletable' => $this->resource->type->is(RoleType::REGULAR),
            'users_count' => $this->resource->users_count,
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
