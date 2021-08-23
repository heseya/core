<?php

namespace App\Http\Resources;

use App\Http\Resources\Swagger\RoleResourceSwagger;
use Illuminate\Http\Request;

class RoleResource extends Resource implements RoleResourceSwagger
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'description' => $this->description,
            'assignable' => true,
        ];
    }

    public function view(Request $request): array
    {
        return [
            'permissions' => $this->getPermissionNames()->sort()->values(),
        ];
    }
}
