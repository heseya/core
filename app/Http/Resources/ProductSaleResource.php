<?php

namespace App\Http\Resources;

use App\Models\Discount;
use App\Traits\GetAllTranslations;
use App\Traits\MetadataResource;
use Illuminate\Http\Request;

/**
 * @property Discount $resource
 */
class ProductSaleResource extends Resource
{
    use GetAllTranslations;
    use MetadataResource;

    public function base(Request $request): array
    {
        $amounts = $this->resource->amounts->isNotEmpty()
            ? PriceResource::collection($this->resource->amounts)
            : null;

        return array_merge([
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'description' => $this->resource->description,
            'percentage' => $this->resource->percentage,
            'amounts' => $amounts,
            'priority' => $this->resource->priority,
            'target_type' => $this->resource->target_type,
            'target_is_allow_list' => $this->resource->target_is_allow_list,
            'active' => $this->resource->active,
            'description_html' => $this->resource->description_html,
            ...$request->boolean('with_translations') ? $this->getAllTranslations('sales.show_hidden') : [],
        ], $this->metadataResource('sales.show_metadata_private'));
    }
}
