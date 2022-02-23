<?php

namespace App\Dtos;

use App\Http\Requests\SeoKeywordsRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;

class SeoKeywordsDto extends Dto
{
    private array $keywords;
    private string|Missing $excluded_id;
    private string|Missing $excluded_model;

    public static function fromFormRequest(SeoKeywordsRequest $request): self
    {
        return new self(
            keywords: $request->input('keywords'),
            excluded_id: $request->input('excluded.id', new Missing()),
            excluded_model: $request->input('excluded.model', new Missing()),
        );
    }

    public function getKeywords(): array
    {
        return $this->keywords;
    }

    public function getExcludedId(): Missing|string
    {
        return $this->excluded_id;
    }

    public function getExcludedModel(): Missing|string
    {
        return $this->excluded_model;
    }
}
