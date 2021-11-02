<?php

namespace App\Dtos;

use App\Http\Requests\RoleUpdateRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;

class RoleUpdateDto extends Dto
{
    private string|Missing $name;
    private string|null|Missing $description;
    private array|Missing $permissions;

    public static function fromRoleUpdateRequest(RoleUpdateRequest $request): self
    {
        return new self(
            name: $request->input('name', new Missing()),
            description: $request->input('description', new Missing()),
            permissions: $request->input('permissions', new Missing()),
        );
    }

    public function getName(): Missing|string
    {
        return $this->name;
    }

    public function getDescription(): Missing|string|null
    {
        return $this->description;
    }

    public function getPermissions(): Missing|array
    {
        return $this->permissions;
    }
}
