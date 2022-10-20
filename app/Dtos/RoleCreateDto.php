<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Traits\MapMetadata;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class RoleCreateDto extends Dto implements InstantiateFromRequest
{
    use MapMetadata;

    private string $name;
    private ?string $description;
    private array $permissions;
    private array|Missing $metadata;

    public static function instantiateFromRequest(FormRequest $request): self
    {
        return new self(
            name: $request->input('name'),
            description: $request->input('description'),
            permissions: $request->input('permissions', []),
            metadata: self::mapMetadata($request),
        );
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
