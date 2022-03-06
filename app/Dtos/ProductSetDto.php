<?php

namespace App\Dtos;

use App\Http\Requests\ProductSetStoreRequest;
use App\Http\Requests\ProductSetUpdateRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;

class ProductSetDto extends Dto
{
    private string $name;
    private string|null|Missing $slug_suffix;
    private bool $slug_override;
    private bool $public;
    private bool $hide_on_index;
    private string|null|Missing $parent_id;
    private array $children_ids;
    private SeoMetadataDto $seo;
    private string|null|Missing $description_html;
    private string|null|Missing $cover_id;
    private array $attributes_ids;

    public static function fromFormRequest(ProductSetStoreRequest|ProductSetUpdateRequest $request): self
    {
        return new self(
            name: $request->input('name'),
            slug_suffix: $request->input('slug_suffix'),
            slug_override: $request->boolean('slug_override', false),
            public: $request->boolean('public', true),
            hide_on_index: $request->boolean('hide_on_index', false),
            parent_id: $request->input('parent_id', null),
            children_ids: $request->input('children_ids', []),
            seo: SeoMetadataDto::fromFormRequest($request),
            description_html: $request->input('description_html'),
            cover_id: $request->input('cover_id'),
            attributes_ids: $request->input('attributes', []),
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlugSuffix(): Missing|string|null
    {
        return $this->slug_suffix;
    }

    public function isSlugOverridden(): bool
    {
        return $this->slug_override;
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function isHiddenOnIndex(): bool
    {
        return $this->hide_on_index;
    }

    public function getParentId(): Missing|string|null
    {
        return $this->parent_id;
    }

    public function getChildrenIds(): array
    {
        return $this->children_ids;
    }

    public function getSeo(): SeoMetadataDto
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

    public function getAttributesIds(): array
    {
        return $this->attributes_ids;
    }
}
