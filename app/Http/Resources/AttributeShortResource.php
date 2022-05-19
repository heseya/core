<?php

namespace App\Http\Resources;

use App\Enums\AttributeType;
use App\Traits\MetadataResource;
use Illuminate\Http\Request;

class AttributeShortResource extends Resource
{
    use MetadataResource;

    public function base(Request $request): array
    {
        [$min, $max] = match ($this->resource->type->value) {
            AttributeType::NUMBER => [$this->resource->min_number, $this->resource->max_number],
            AttributeType::DATE => [$this->resource->min_date, $this->resource->max_date],
            default => [null, null],
        };

        return array_merge([
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'description' => $this->resource->description,
            'min' => $min,
            'max' => $max,
            'type' => $this->resource->type,
            'global' => $this->resource->global,
            'sortable' => $this->resource->sortable,
        ], $this->metadataResource('attributes.show_metadata_private'));
    }
}
