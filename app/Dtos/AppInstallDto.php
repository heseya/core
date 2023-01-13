<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\AppStoreRequest;
use App\Traits\MapMetadata;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class AppInstallDto extends Dto implements InstantiateFromRequest
{
    use MapMetadata;

    private string $url;
    private ?string $name;
    private ?string $licenceKey;
    private array $allowedPermissions;
    private array $publicAppPermissions;
    private array|Missing $metadata;

    public static function instantiateFromRequest(FormRequest|AppStoreRequest $request): self
    {
        return new self(
            url: $request->input('url'),
            name: $request->input('name'),
            licenceKey: $request->input('licence_key'),
            allowedPermissions: $request->input('allowed_permissions'),
            publicAppPermissions: $request->input('public_app_permissions'),
            metadata: self::mapMetadata($request),
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
