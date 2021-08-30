<?php

namespace App\Http\Resources;

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
            'assignable' => Auth::user()->hasAllPermissions(
                $this->getAllPermissions(),
            ),
        ];
    }

    public function view(Request $request): array
    {
        return [
            'permissions' => $this->getPermissionNames()->sort()->values(),
        ];
    }
}
