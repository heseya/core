<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductAttributeResource extends ProductAttributeShortResource
{
    public function base(Request $request): array
    {
        return array_merge(
            parent::base($request),
            [
                'id' => $this->getKey(),
                'description' => $this->description,
                'type' => Str::lower($this->type->key),
                'global' => $this->global,
            ]
        );
    }
}
