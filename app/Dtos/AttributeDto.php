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
        private string|Missing $id,
        private string $name,
        private string $slug,
        private string|null|Missing $description,
        private string $type,
        private bool $global,
        private bool $sortable,
        private array|Missing $metadata,
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

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getDescription(): string|null|Missing
    {
        return $this->description;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isGlobal(): bool
    {
        return $this->global;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }
}
