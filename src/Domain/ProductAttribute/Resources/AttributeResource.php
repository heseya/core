<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Resources;

use App\Http\Resources\Resource;
use App\Traits\GetAllTranslations;
use App\Traits\MetadataResource;
use Domain\ProductAttribute\Enums\AttributeType;
use Domain\ProductAttribute\Models\Attribute;
use Illuminate\Http\Request;

/**
 * @property Attribute $resource
 */
final class AttributeResource extends Resource
{
    use GetAllTranslations;
    use MetadataResource;

    /**
     * @return array<string, mixed>
     */
    public function base(Request $request): array
    {
        [$min, $max] = match ($this->resource->type) {
            AttributeType::NUMBER => [$this->resource->min_number, $this->resource->max_number],
            AttributeType::DATE => [$this->resource->min_date, $this->resource->max_date],
            default => [null, null],
        };

        return array_merge([
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'description' => $this->resource->description,
            'min' => $min,
            'max' => $max,
            'type' => $this->resource->type,
            'global' => $this->resource->global,
            'sortable' => $this->resource->sortable,
            'published' => $this->resource->published,
            ...$request->boolean('with_translations') ? $this->getAllTranslations() : [],
        ], $this->metadataResource('attributes.show_metadata_private'));
    }
}
