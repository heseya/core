<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ProductAttributeResource extends ProductAttributeShortResource
{
    public function base(Request $request): array
    {
        return array_merge(
            parent::base($request),
            [
                'id' => $this->getKey(),
                'slug' => $this->slug,
                'description' => $this->description,
                'type' => $this->type->value,
                'global' => $this->global,
                'sortable' => $this->sortable,
            ]
        );
    }
}
