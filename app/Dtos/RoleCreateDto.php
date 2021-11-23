<?php

namespace App\Dtos;

use App\Dtos\Contracts\DtoContract;
use App\Dtos\Contracts\InstantiateFromRequest;
use Illuminate\Http\Request;

class RoleCreateDto implements DtoContract, InstantiateFromRequest
{
    public function __construct(
        private string $name,
        private ?string $description,
        private array $permissions,
    ) {
    }

    public static function instantiateFromRequest(Request $request): self
    {
        return new self(
            $request->input('name'),
            $request->input('description'),
            $request->input('permissions', []),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'permissions' => $this->getPermissions(),
        ];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }
}
