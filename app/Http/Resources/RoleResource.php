<?php

namespace App\Http\Resources;

use App\Enums\RoleType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'assignable' => Auth::user() !== null
            && $this->resource->type->isNot(RoleType::UNAUTHENTICATED)
            && $this->resource->type->isNot(RoleType::AUTHENTICATED)
                ? Auth::user()->hasAllPermissions(
                    $this->resource->getAllPermissions(),
                ) : false,
            'deletable' => $this->resource->type->is(RoleType::REGULAR),
        ];
    }

    public function view(Request $request): array
    {
        return [
            'permissions' => $this->resource->getPermissionNames()->sort()->values(),
            'locked_permissions' => $this->resource->type->is(RoleType::OWNER),
        ];
    }
}
