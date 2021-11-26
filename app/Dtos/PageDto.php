<?php

namespace App\Dtos;

use App\Http\Requests\PageStoreRequest;
use App\Http\Requests\PageUpdateRequest;
use Heseya\Dto\Dto;

class PageDto extends Dto
{
    private string $name;
    private string $slug;
    private bool $public;
    private string $content_html;
    private SeoMetadataDto $seo;

    public static function fromFormRequest(PageStoreRequest|PageUpdateRequest $request)
    {
        return new self(
            name: $request->input('name'),
            slug: $request->input('slug'),
            public: $request->input('public'),
            content_html: $request->input('content_html'),
            seo: SeoMetadataDto::fromFormRequest($request),
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
