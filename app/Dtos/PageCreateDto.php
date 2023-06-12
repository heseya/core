<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\PageStoreRequest;
use App\Http\Requests\PageUpdateRequest;
use App\Traits\MapMetadata;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class PageCreateDto extends Dto implements InstantiateFromRequest
{
    use MapMetadata;

    private string $name;
    private string $slug;
    private bool $public;
    private string $content_html;
    private SeoMetadataDto|Missing $seo;
    private array|Missing $metadata;

    public static function instantiateFromRequest(FormRequest|PageStoreRequest|PageUpdateRequest $request): self
    {
        return new self(
            name: $request->input('name'),
            slug: $request->input('slug'),
            public: $request->input('public'),
            content_html: $request->input('content_html'),
            seo: $request->has('seo') ? SeoMetadataDto::instantiateFromRequest($request) : new Missing(),
            metadata: self::mapMetadata($request),
        );
    }

    public function getSeo(): SeoMetadataDto|Missing
    {
        return $this->seo;
    }
}
