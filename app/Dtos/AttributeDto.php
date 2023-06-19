<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\AttributeStoreRequest;
use App\Http\Requests\AttributeUpdateRequest;
use App\Traits\MapMetadata;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class AttributeDto extends Dto implements InstantiateFromRequest
{
    use MapMetadata;

    public function __construct(
        public readonly string|Missing $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly string|null|Missing $description,
        public readonly string $type,
        public readonly bool $global,
        public readonly bool $sortable,
        public readonly array|Missing $metadata,
    ) {
    }

    public static function instantiateFromRequest(
        FormRequest|AttributeStoreRequest|AttributeUpdateRequest $request
    ): self {
        return new self(
            id: $request->input('id', new Missing()),
            name: $request->input('name'),
            slug: $request->input('slug'),
            description: $request->input('description', new Missing()),
            type: $request->input('type'),
            global: $request->input('global'),
            sortable: $request->input('sortable'),
            metadata: self::mapMetadata($request),
        );
    }
}
