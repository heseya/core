<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AttributeResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'description' => $this->description,
            'type' => Str::lower($this->type->key),
            'searchable' => $this->searchable,
            'options' => $this->options ? AttributeOptionResource::make($this->options) : null,
        ];
    }
}
