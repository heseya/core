<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\SeoKeywordsRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class SeoKeywordsDto extends Dto implements InstantiateFromRequest
{
    private array $keywords;
    private Missing|string $excluded_id;
    private Missing|string $excluded_model;

    public static function instantiateFromRequest(FormRequest|SeoKeywordsRequest $request): self
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
