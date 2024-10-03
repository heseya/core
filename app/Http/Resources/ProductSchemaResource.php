<?php

namespace App\Http\Resources;

use App\Traits\ModifyLangFallback;
use Domain\ProductSchema\Models\Schema;
use Illuminate\Http\Request;

/**
 * @property Schema $resource
 */
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
