<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class SchemaResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'type' => $this->type->key,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'hidden' => $this->hidden,
            'required' => $this->required,
            'available' => $this->available,
            'max' => $this->max,
            'min' => $this->min,
            'step' => $this->step,
            'default' => $this->default,
            'pattern' => $this->pattern,
            'validation' => $this->validation,
            'options' => OptionResource::collection($this->options),
            'used_schemas' => $this->usedSchemas->map(fn ($schema) => $schema->getKey()),
        ];
    }

    public function view(Request $request): array
    {
        return [
            'products' => ProductResource::collection($this->products),
        ];
    }
}
