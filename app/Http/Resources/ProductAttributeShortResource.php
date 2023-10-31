<?php

namespace App\Http\Resources;

use App\Models\ProductAttribute;
use App\Traits\ModifyLangFallback;
use Domain\ProductAttribute\Resources\AttributeOptionResource;
use Illuminate\Http\Request;

/**
 * @property ProductAttribute $resource
 */
class ProductAttributeShortResource extends Resource
{
    use ModifyLangFallback;

    public function base(Request $request): array
    {
        return [
            'name' => $this->resource->attribute?->name,
            'slug' => $this->resource->attribute?->slug,
            'selected_options' => AttributeOptionResource::collection(
                $this->resource->options ?? $this->resource->attribute?->options ?? [],
            ),
        ];
    }

    public function toArray($request): array
    {
        $previousSettings = $this->getCurrentLangFallbackSettings();
        $this->setAnyLangFallback();
        $result = parent::toArray($request);
        $this->setLangFallbackSettings(...$previousSettings);

        return $result;
    }
}
