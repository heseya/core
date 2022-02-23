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
            'description' => $this->description,
            'type' => $this->type,
            'global' => $this->global,
            'options' => AttributeOptionResource::collection($this->options),
        ];
    }
}
