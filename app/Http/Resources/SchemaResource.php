<?php

namespace App\Http\Resources;

use App\Traits\GetAllTranslations;
use App\Traits\MetadataResource;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SchemaResource extends Resource
{
    use GetAllTranslations;
    use MetadataResource;

    /**
     * @return array<string, mixed>
     */
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'type' => Str::lower($this->resource->type->name),
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'prices' => PriceResource::collection($this->resource->prices),
            'hidden' => $this->resource->hidden,
            'required' => $this->resource->required,
            'available' => $this->resource->available,
            'max' => $this->resource->max,
            'min' => $this->resource->min,
            'step' => $this->resource->step,
            'default' => $this->resource->default,
            'pattern' => $this->resource->pattern,
            'validation' => $this->resource->validation,
            'shipping_time' => $this->resource->shipping_time,
            'shipping_date' => $this->resource->shipping_date,
            'options' => OptionResource::collection($this->resource->options),
            'used_schemas' => $this->resource->usedSchemas->map(fn ($schema) => $schema->getKey()),
            ...$this->metadataResource('schemas.show_metadata_private'),
            ...$request->boolean('with_translations') ? $this->getAllTranslations() : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function view(Request $request): array
    {
        return [
            'products' => ProductResource::collection($this->resource->products),
        ];
    }
}
