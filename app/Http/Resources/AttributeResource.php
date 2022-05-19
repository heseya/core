<?php

namespace App\Http\Resources;

use App\Traits\MetadataResource;
use Illuminate\Http\Request;

class AttributeResource extends AttributeShortResource
{
    use MetadataResource;

    public function base(Request $request): array
    {
        return parent::base($request) + [
            'options' => AttributeOptionResource::collection($this->resource->options),
        ];
    }
}
