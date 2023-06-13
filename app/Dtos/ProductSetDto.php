<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\ProductSetStoreRequest;
use App\Http\Requests\ProductSetUpdateRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\DtoException;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class ProductSetDto extends Dto implements InstantiateFromRequest
{
    public function __construct(
        private string|Missing $id,
        private string $name,
        private string|null|Missing $slug_suffix,
        private bool $slug_override,
        private bool $public,
        private string|null|Missing $parent_id,
        private array $children_ids,
        private SeoMetadataDto|Missing $seo,
        private string|null|Missing $description_html,
        private string|null|Missing $cover_id,
        private array|null|Missing $attributes_ids,
        private array|Missing $metadata,
    ) {
    }

    /**
     * @throws DtoException
     */
    public static function instantiateFromRequest(
        FormRequest|ProductSetStoreRequest|ProductSetUpdateRequest $request
    ): self {
        return new self(
            id: $request->input('id') ?? new Missing(),
            name: $request->input('name'),
            slug_suffix: $request->input('slug_suffix'),
            slug_override: $request->boolean('slug_override', false),
            public: $request->boolean('public', true),
            parent_id: $request->input('parent_id', null),
            children_ids: $request->input('children_ids', []),
            seo: $request->has('seo') ? SeoMetadataDto::instantiateFromRequest($request) : new Missing(),
            description_html: $request->input('description_html'),
            cover_id: $request->input('cover_id'),
            attributes_ids: $request->input('attributes'),
            metadata: ProductCreateDto::mapMetadata($request),
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

    public function getParentId(): Missing|string|null
    {
        return $this->parent_id;
    }

    public function getChildrenIds(): array
    {
        return $this->children_ids;
    }

    public function getSeo(): SeoMetadataDto|Missing
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

    public function getMetadata(): Missing|array
    {
        return $this->metadata;
    }
}
