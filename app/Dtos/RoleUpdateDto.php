<?php

namespace App\Dtos;

use App\Dtos\Contracts\DtoContract;
use App\Dtos\Contracts\InstantiateFromRequest;
use Illuminate\Http\Request;

class RoleUpdateDto implements DtoContract, InstantiateFromRequest
{
    public function __construct(
        private ?string $name,
        private ?string $description,
        private ?array $permissions,
    ) {
    }

    public function toArray(): array
    {
        $data = [];

        if ($this->getName() !== null) {
            $data['name'] = $this->getName();
        }

        if ($this->getDescription() !== null) {
            $data['description'] = $this->getDescription();
        }

        if ($this->getPermissions() !== null) {
            $data['permissions'] = $this->getPermissions();
        }

        return $data;
    }

    public static function instantiateFromRequest(Request $request): self
    {
        return new self(
            $request->input('name', null),
            $request->input('description', null),
            $request->input('permissions', null),
        );
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getPermissions(): ?array
    {
        return $this->permissions;
    }
}
