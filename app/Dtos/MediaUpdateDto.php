<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\MediaUpdateRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class MediaUpdateDto extends Dto implements InstantiateFromRequest
{
    private string|null|Missing $alt;
    private string|null|Missing $slug;

    public static function instantiateFromRequest(FormRequest|MediaUpdateRequest $request): self
    {
        return new self(
            alt: $request->input('alt', new Missing()),
            slug: $request->input('slug', new Missing()),
        );
    }

    public function getAlt(): string|null|Missing
    {
        return $this->alt;
    }

    public function getSlug(): string|null|Missing
    {
        return $this->slug;
    }
}
