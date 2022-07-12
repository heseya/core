<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Http\Request;

class UpdateProfileDto extends Dto implements InstantiateFromRequest
{
    private string|Missing $name;
    private UserPreferencesDto $preferences;
    private array $consents;

    public static function instantiateFromRequest(Request $request): self
    {
        return new self(
            name: $request->input('name', new Missing()),
            preferences: UserPreferencesDto::instantiateFromRequest($request),
            consents: $request->input('consents', []),
        );
    }

    public function getName(): Missing|string
    {
        return $this->name;
    }

    public function getPreferences(): UserPreferencesDto
    {
        return $this->preferences;
    }

    public function getConsents(): array
    {
        return $this->consents;
    }
}
