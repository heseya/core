<?php

namespace App\Http\Resources;

use App\Models\Discount;
use App\Traits\GetAllTranslations;
use App\Traits\MetadataResource;
use Domain\ProductSet\Resources\ProductSetResource;
use Domain\Seo\Resources\SeoMetadataResource;
use Illuminate\Http\Request;

/**
 * @property Discount $resource
 */
class SaleResource extends Resource
{
    use GetAllTranslations;
    use MetadataResource;

    public function base(Request $request): array
    {
        if (isset($this->resource->pivot, $this->resource->pivot->type)) {
            // @phpstan-ignore-next-line
            $this->resource->value = $this->resource->pivot->value;
            $this->resource->type = $this->resource->pivot->type;
        }

        return array_merge([
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'description' => $this->resource->description,
            'value' => $this->resource->value,
            'type' => $this->resource->type,
            'priority' => $this->resource->priority,
            'uses' => $this->resource->uses,
            'target_type' => $this->resource->target_type,
            'target_is_allow_list' => $this->resource->target_is_allow_list,
            'active' => $this->resource->active,
            'description_html' => $this->resource->description_html,
            'published' => $this->resource->published,
            ...$request->boolean('with_translations') ? $this->getAllTranslations('sales.show_hidden') : [],
        ], $this->metadataResource('sales.show_metadata_private'));
    }

    public function view(Request $request): array
    {
        return [
            'seo' => SeoMetadataResource::make($this->resource->seo),
            'condition_groups' => ConditionGroupResource::collection($this->resource->conditionGroups),
            'target_products' => ProductResource::collection($this->resource->products),
            'target_sets' => ProductSetResource::collection($this->resource->productSets),
            'target_shipping_methods' => ShippingMethodResource::collection($this->resource->shippingMethods),
        ];
    }
}
