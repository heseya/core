<?php

namespace App\Http\Resources;

use App\Traits\ModifyLangFallback;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Resources\AttributeOptionResource;
use Illuminate\Http\Request;

/**
 * @property Attribute $resource
 */
class AttributeShortResource extends Resource
{
    use ModifyLangFallback;

    public function base(Request $request): array
    {
        return [
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'selected_options' => AttributeOptionResource::collection(
                $this->resource->product_attribute_pivot->options ?? $this->resource->options,
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
