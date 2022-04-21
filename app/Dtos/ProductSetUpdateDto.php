<?php

namespace App\Dtos;

use App\Http\Requests\ProductSetStoreRequest;
use App\Http\Requests\ProductSetUpdateRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;

class ProductSetUpdateDto extends Dto
{
    private string|Missing $name;
    private string|null|Missing $slug_suffix;
    private bool|Missing $slug_override;
    private bool|Missing $public;
    private bool|Missing $hide_on_index;
    private string|null|Missing $parent_id;
    private array|Missing $children_ids;
    private SeoMetadataDto $seo;
    private string|null|Missing $description_html;
    private string|null|Missing $cover_id;
    private array|null|Missing $attributes_ids;

    public static function fromFormRequest(ProductSetStoreRequest|ProductSetUpdateRequest $request): self
    {
        return new self(
            name: $request->input('name', new Missing()),
            slug_suffix: $request->input('slug_suffix', new Missing()),
            slug_override: $request->input('slug_override', new Missing()),
            public: $request->input('public', new Missing()),
            hide_on_index: $request->input('hide_on_index', new Missing()),
            parent_id: $request->input('parent_id', new Missing()),
            children_ids: $request->input('children_ids', new Missing()),
            seo: SeoMetadataDto::fromFormRequest($request),
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

    public function isSlugOverridden(): Missing|bool
    {
        return $this->slug_override;
    }

    public function isPublic(): Missing|bool
    {
        return $this->public;
    }

    public function isHiddenOnIndex(): Missing|bool
    {
        return $this->hide_on_index;
    }

    public function getParentId(): Missing|string|null
    {
        return $this->parent_id;
    }

    public function getChildrenIds(): Missing|array
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

    public function getAttributesIds(): Missing|null|array
    {
        return $this->attributes_ids;
    }
}
