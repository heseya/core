<?php

namespace App\Http\Resources;

use App\Http\Resources\Swagger\ProfileResourceSwagger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProfileResource extends Resource implements ProfileResourceSwagger
{
    private ?string $permissionPrefix = null;

    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'avatar' => $this->avatar,
            'permissions' => $this->getAllPermissions()
                ->map(
                    fn ($perm) => Str::startsWith($perm->name, $this->permissionPrefix) ?
                    Str::substr($perm->name, Str::length($this->permissionPrefix)) : $perm->name,
                )->sort()
                ->values(),
        ];
    }

    public function stripedPermissionPrefix(?string $prefix): self
    {
        $this->permissionPrefix = $prefix;

        return $this;
    }
}
