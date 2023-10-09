<?php

namespace App\Http\Resources;

use App\Traits\ModifyLangFallback;
use Domain\ProductAttribute\Resources\AttributeOptionResource;
use Illuminate\Http\Request;

class ProductAttributeShortResource extends Resource
{
    use ModifyLangFallback;

    public function base(Request $request): array
    {
        return [
            'name' => $this->resource->name,
            'selected_options' => AttributeOptionResource::collection(
                $this->resource->pivot->options ?? $this->resource->options,
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
