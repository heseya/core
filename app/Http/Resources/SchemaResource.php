<?php

namespace App\Http\Resources;

use App\Traits\GetAllTranslations;
use App\Traits\MetadataResource;
use Illuminate\Http\Request;

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
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'hidden' => $this->resource->hidden,
            'required' => $this->resource->required,
            'available' => $this->resource->available,
            'max' => $this->resource->max,
            'min' => $this->resource->min,
            'step' => $this->resource->step,
            'default' => $this->resource->default,
            'shipping_time' => $this->resource->shipping_time,
            'shipping_date' => $this->resource->shipping_date,
            'product_id' => $this->resource->product_id,
            'options' => OptionResource::collection($this->resource->options),
            'used_schemas' => $this->resource->usedSchemas->map(fn ($schema) => $schema->getKey()),
            ...$this->metadataResource('schemas.show_metadata_private'),
            'published' => $this->resource->published,
            ...$request->boolean('with_translations') ? $this->getAllTranslations('schemas.show_hidden') : [],
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
