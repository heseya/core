<?php

namespace App\Http\Resources;

use App\Traits\MetadataResource;
use Illuminate\Http\Request;

class AttributeOptionResource extends Resource
{
    use MetadataResource;

    public function base(Request $request): array
    {
        return array_merge([
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'index' => $this->resource->index,
            'value_number' => $this->resource->value_number,
            'value_date' => $this->resource->value_date,
            'attribute_id' => $this->resource->attribute_id,
        ], $this->metadataResource('attributes.show_metadata_private'));
    }
}
