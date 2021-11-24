<?php

namespace App\Http\Resources;

use App\Enums\RoleType;
use App\Http\Resources\Swagger\RoleResourceSwagger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleResource extends Resource implements RoleResourceSwagger
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'description' => $this->description,
            'assignable' => $this->type->is(RoleType::UNAUTHENTICATED) ? false : Auth::user()->hasAllPermissions(
                $this->getAllPermissions(),
            ),
            'deletable' => $this->type->is(RoleType::REGULAR),
        ];
    }

    public function view(Request $request): array
    {
        return [
            'permissions' => $this->getPermissionNames()->sort()->values(),
            'locked_permissions' => $this->type->is(RoleType::OWNER),
        ];
    }
}
