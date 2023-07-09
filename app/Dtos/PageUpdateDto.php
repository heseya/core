<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\PageStoreRequest;
use App\Http\Requests\PageUpdateRequest;
use App\Traits\MapMetadata;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class PageUpdateDto extends Dto implements InstantiateFromRequest
{
    use MapMetadata;

    private Missing|string $name;
    private Missing|string $slug;
    private bool|Missing $public;
    private Missing|string $content_html;
    private Missing|SeoMetadataDto $seo;
    private array|Missing $metadata;

    public static function instantiateFromRequest(FormRequest|PageStoreRequest|PageUpdateRequest $request): self
    {
        return new self(
            name: $request->has('name') ? $request->input('name') : new Missing(),
            slug: $request->has('slug') ? $request->input('slug') : new Missing(),
            public: $request->has('public') ? $request->input('public') : new Missing(),
            content_html: $request->has('content_html') ? $request->input('content_html') : new Missing(),
            seo: $request->has('seo') ? SeoMetadataDto::instantiateFromRequest($request) : new Missing(),
            metadata: self::mapMetadata($request),
        );
    }

    public function getSeo(): Missing|SeoMetadataDto
    {
        return $this->seo;
    }
}
