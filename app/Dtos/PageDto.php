<?php

namespace App\Dtos;

use App\Http\Requests\PageStoreRequest;
use App\Http\Requests\PageUpdateRequest;
use Heseya\Dto\Dto;

class PageDto extends Dto
{
    /** @var array<string, PageTranslationDto> */
    private array $translations;
    /** @var string[] */
    private array $published;
    private string $slug;
    private bool $public;
    private SeoMetadataDto $seo;

    public static function fromFormRequest(PageStoreRequest|PageUpdateRequest $request)
    {
        $translations = array_map(fn ($data) => PageTranslationDto::fromParams(
            $data['name'],
            $data['content_html'],
        ), $request->input('translations', []));

        return new self(
            translations: $translations,
            published: $request->input('published', []),
            slug: $request->input('slug'),
            public: $request->input('public'),
            seo: SeoMetadataDto::fromFormRequest($request),
        );
    }

    /**
     * @return PageTranslationDto[]
     */
    public function getTranslations(): array
    {
        return $this->translations;
    }

    /**
     * @return string[]
     */
    public function getPublished(): array
    {
        return $this->published;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function getSeo(): SeoMetadataDto
    {
        return $this->seo;
    }
}
