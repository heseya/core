<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\ProductSetStoreRequest;
use App\Http\Requests\ProductSetUpdateRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class ProductSetUpdateDto extends Dto implements InstantiateFromRequest
{
    private Missing|string $name;
    private Missing|string|null $slug_suffix;
    private bool|Missing $slug_override;
    private bool|Missing $public;
    private Missing|string|null $parent_id;
    private array|Missing $children_ids;
    private Missing|SeoMetadataDto $seo;
    private Missing|string|null $description_html;
    private Missing|string|null $cover_id;
    private array|Missing|null $attributes_ids;

    public static function instantiateFromRequest(
        FormRequest|ProductSetStoreRequest|ProductSetUpdateRequest $request,
    ): self {
        return new self(
            name: $request->input('name', new Missing()),
            slug_suffix: $request->input('slug_suffix', new Missing()),
            slug_override: $request->input('slug_override', new Missing()),
            public: $request->input('public', new Missing()),
            parent_id: $request->input('parent_id', new Missing()),
            children_ids: $request->input('children_ids', new Missing()),
            seo: $request->has('seo') ? SeoMetadataDto::instantiateFromRequest($request) : new Missing(),
            description_html: $request->input('description_html', new Missing()),
            cover_id: $request->input('cover_id', new Missing()),
            attributes_ids: $request->input('attributes', new Missing()),
        );
    }

    public function getName(): Missing|string
    {
        return $this->name;
    }

    public function getSlugSuffix(): Missing|string|null
    {
        return $this->slug_suffix;
    }

    public function isSlugOverridden(): bool|Missing
    {
        return $this->slug_override;
    }

    public function isPublic(): bool|Missing
    {
        return $this->public;
    }

    public function getParentId(): Missing|string|null
    {
        return $this->parent_id;
    }

    public function getChildrenIds(): array|Missing
    {
        return $this->children_ids;
    }

    public function getSeo(): Missing|SeoMetadataDto
    {
        return $this->seo;
    }

    public function getDescriptionHtml(): Missing|string|null
    {
        return $this->description_html;
    }

    public function getCoverId(): Missing|string|null
    {
        return $this->cover_id;
    }

    public function getAttributesIds(): array|Missing|null
    {
        return $this->attributes_ids;
    }
}
