<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProfileResource extends Resource
{
    private ?string $permissionPrefix = null;

    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'avatar' => $this->resource->avatar,
            'permissions' => $this->resource->getAllPermissions()
                ->map(
                    fn ($perm) => Str::startsWith($perm->name, $this->permissionPrefix) ?
                    Str::substr(
                        $perm->name,
                        $this->permissionPrefix ? Str::length($this->permissionPrefix) : 0,
                    ) : $perm->name,
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
