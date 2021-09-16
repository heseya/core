<?php

namespace App\Http\Resources;

use App\Http\Resources\Swagger\ProfileResourceSwagger;
use Illuminate\Http\Request;

class ProfileResource extends Resource implements ProfileResourceSwagger
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'avatar' => $this->avatar,
            'permissions' => $this->getAllPermissions()
                ->map(fn ($perm) => $perm->name)
                ->sort()
                ->values(),
        ];
    }
}
