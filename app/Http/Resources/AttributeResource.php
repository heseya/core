<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class AttributeResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'type' => $this->type,
            'global' => $this->global,
            'sortable' => $this->sortable,
            'options' => AttributeOptionResource::collection($this->options),
        ];
    }
}
