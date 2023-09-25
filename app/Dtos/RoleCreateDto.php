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
    private bool|Missing $is_registration_role;
    private array $permissions;
    private array|Missing $metadata;
    private bool|Missing $is_joinable;

    public static function instantiateFromRequest(FormRequest $request): self
    {
        return new self(
            name: $request->input('name'),
            description: $request->input('description'),
            is_registration_role: $request->input('is_registration_role', new Missing()),
            permissions: $request->input('permissions', []),
            metadata: self::mapMetadata($request),
            is_joinable: $request->input('is_joinable', new Missing()),
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

    public function getIsRegistrationRole(): bool|Missing
    {
        return $this->is_registration_role;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function getIsJoinable(): bool|Missing
    {
        return $this->is_joinable;
    }
}
