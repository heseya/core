<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\RoleUpdateRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class RoleUpdateDto extends Dto implements InstantiateFromRequest
{
    private Missing|string $name;
    private Missing|string|null $description;
    private bool|Missing $is_registration_role;
    private array|Missing $permissions;

    public static function instantiateFromRequest(FormRequest|RoleUpdateRequest $request): self
    {
        return new self(
            name: $request->input('name', new Missing()),
            description: $request->input('description', new Missing()),
            is_registration_role: $request->input('is_registration_role', new Missing()),
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

    public function getIsRegistrationRole(): bool|Missing
    {
        return $this->is_registration_role;
    }

    public function getPermissions(): array|Missing
    {
        return $this->permissions;
    }
}
