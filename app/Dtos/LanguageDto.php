<?php

namespace App\Dtos;

use App\Http\Requests\LanguageCreateRequest;
use App\Http\Requests\LanguageUpdateRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;

class LanguageDto extends Dto
{
    private Missing|string $iso;
    private Missing|string $name;
    private bool|Missing $default;
    private bool|Missing $hidden;

    public static function instantiateFromRequest(LanguageCreateRequest|LanguageUpdateRequest $request): self
    {
        return new self(
            iso: $request->input('iso', new Missing()),
            name: $request->input('name', new Missing()),
            default: $request->input('default', new Missing()),
            hidden: $request->input('hidden', new Missing()),
        );
    }

    public function getIso(): Missing|string
    {
        return $this->iso;
    }

    public function getName(): Missing|string
    {
        return $this->name;
    }

    public function getDefault(): bool|Missing
    {
        return $this->default;
    }

    public function getHidden(): bool|Missing
    {
        return $this->hidden;
    }
}
