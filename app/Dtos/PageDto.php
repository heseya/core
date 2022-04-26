<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\PageStoreRequest;
use App\Http\Requests\PageUpdateRequest;
use Heseya\Dto\Dto;
use Illuminate\Foundation\Http\FormRequest;

class PageDto extends Dto implements InstantiateFromRequest
{
    private string $name;
    private string $slug;
    private bool $public;
    private string $content_html;
    private SeoMetadataDto $seo;

    public static function instantiateFromRequest(FormRequest|PageStoreRequest|PageUpdateRequest $request): self
    {
        return new self(
            name: $request->input('name'),
            slug: $request->input('slug'),
            public: $request->input('public'),
            content_html: $request->input('content_html'),
            seo: SeoMetadataDto::instantiateFromRequest($request),
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function getContentHtml(): string
    {
        return $this->content_html;
    }

    public function getSeo(): SeoMetadataDto
    {
        return $this->seo;
    }
}
