<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductAttributeOptionResource extends ProductAttributeOptionShortResource
{
    public function base(Request $request): array
    {
        return array_merge(
            parent::base($request),
            [
                'id' => $this->attribute->getKey(),
                'description' => $this->attribute->description,
                'type' => Str::lower($this->attribute->type->key),
                'global' => $this->attribute->global,
            ]
        );
    }
}
