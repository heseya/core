<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SchemaResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'type' => Str::lower($this->resource->type->key),
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'price' => $this->resource->price,
            'hidden' => $this->resource->hidden,
            'required' => $this->resource->required,
            'available' => $this->resource->available,
            'max' => $this->resource->max,
            'min' => $this->resource->min,
            'step' => $this->resource->step,
            'default' => $this->resource->default,
            'pattern' => $this->resource->pattern,
            'validation' => $this->resource->validation,
            'options' => OptionResource::collection($this->resource->options),
            'used_schemas' => $this->resource->usedSchemas->map(fn ($schema) => $schema->getKey()),
        ];
    }

    public function view(Request $request): array
    {
        return [
            'products' => ProductResource::collection($this->resource->products),
        ];
    }
}
