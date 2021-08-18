<?php

namespace App\Http\Resources;

use App\Http\Resources\Swagger\PermissionResourceSwagger;
use Illuminate\Http\Request;

class PermissionResource extends Resource implements PermissionResourceSwagger
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
}
