<?php

namespace App\Dtos;

use App\Http\Requests\MediaUpdateRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;

class MediaUpdateDto extends Dto
{
    private string|Missing $alt;
    private string|Missing $slug;

    public static function instantiateFromRequest(MediaUpdateRequest $request): self
    {
        return new self(
            alt: $request->input('alt', new Missing()),
            slug: $request->input('slug', new Missing()),
        );
    }

    public function getAlt(): Missing|string
    {
        return $this->alt;
    }

    public function getSlug(): Missing|string
    {
        return $this->slug;
    }
}
