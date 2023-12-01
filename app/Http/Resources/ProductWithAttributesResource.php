<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ProductWithAttributesResource extends ProductResource
{
    public function base(Request $request): array
    {
        return array_merge(parent::base($request), [
            'attributes' => ProductAttributeShortResource::collection($this->resource->productAttributes),
        ]);
    }
}
