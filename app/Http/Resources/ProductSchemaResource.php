<?php

namespace App\Http\Resources;

use App\Traits\ModifyLangFallback;
use Illuminate\Http\Request;

class ProductSchemaResource extends SchemaResource
{
    use ModifyLangFallback;

    public function base(Request $request): array
    {
        return array_merge(parent::base($request), [
            'options' => ProductSchemaOptionResource::collection($this->resource->options),
        ]);
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
