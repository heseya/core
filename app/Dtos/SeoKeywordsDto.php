<?php

namespace App\Dtos;

use App\Http\Requests\SeoKeywordsRequest;
use Heseya\Dto\Dto;

class SeoKeywordsDto extends Dto
{
    private array $keywords;

    public static function fromFormRequest(SeoKeywordsRequest $request): self
    {
        return new self(
            keywords: $request->input('keywords'),
        );
    }

    public function getKeywords(): array
    {
        return $this->keywords;
    }
}
