<?php

namespace App\Http\Resources;

use App\Enums\RoleType;
use App\Traits\MetadataResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleResource extends Resource
{
    use MetadataResource;

    public function base(Request $request): array
    {
        return array_merge([
            'id' => $this->getKey(),
            'name' => $this->name,
            'description' => $this->description,
            'assignable' => Auth::user() !== null
            && $this->type->isNot(RoleType::UNAUTHENTICATED)
            && $this->type->isNot(RoleType::AUTHENTICATED)
                ? Auth::user()->hasAllPermissions(
                    $this->getAllPermissions(),
                ) : false,
            'deletable' => $this->type->is(RoleType::REGULAR),
        ], $this->metadataResource('roles.show_metadata_private'));
    }

    public function view(Request $request): array
    {
        return [
            'permissions' => $this->getPermissionNames()->sort()->values(),
            'locked_permissions' => $this->type->is(RoleType::OWNER),
        ];
    }
}
