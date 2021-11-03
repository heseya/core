<?php

namespace App\Dtos;

use App\Http\Requests\AppStoreRequest;
use Heseya\Dto\Dto;

class AppInstallDto extends Dto
{
    private string $url;
    private ?string $name;
    private ?string $licenceKey;
    private array $allowedPermissions;
    private array $publicAppPermissions;

    public static function fromAppStoreRequest(AppStoreRequest $request): self
    {
        return new self(
            url: $request->input('url'),
            name: $request->input('name'),
            licenceKey: $request->input('licence_key'),
            allowedPermissions: $request->input('allowed_permissions'),
            publicAppPermissions: $request->input('public_app_permissions'),
        );
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getLicenceKey(): ?string
    {
        return $this->licenceKey;
    }

    public function getAllowedPermissions(): array
    {
        return $this->allowedPermissions;
    }

    public function getPublicAppPermissions(): array
    {
        return $this->publicAppPermissions;
    }
}
