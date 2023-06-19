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
        public readonly string|Missing $id,
        public readonly string $name,
        public readonly string|null|Missing $slug_suffix,
        public readonly bool $slug_override,
        public readonly bool $public,
        public readonly string|null|Missing $parent_id,
        public readonly array $children_ids,
        public readonly SeoMetadataDto|Missing $seo,
        public readonly string|null|Missing $description_html,
        public readonly string|null|Missing $cover_id,
        public readonly array|null|Missing $attributes_ids,
        public readonly array|Missing $metadata,
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
}
