<?php

namespace App\Http\Resources;

use App\Models\Discount;
use App\Traits\MetadataResource;
use Illuminate\Http\Request;

/**
 * @property Discount $resource
 */
class SaleResource extends Resource
{
    use MetadataResource;

    public function base(Request $request): array
    {
        if (isset($this->resource->pivot, $this->resource->pivot->type)) {
            // @phpstan-ignore-next-line
            $this->resource->type = $this->resource->pivot->type;
            // @phpstan-ignore-next-line
            $this->resource->value = $this->resource->pivot->value;
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
        ], $this->metadataResource('sales.show_metadata_private'));
    }

    public function view(Request $request): array
    {
        return [
            'description_html' => $this->resource->description_html,
            'condition_groups' => ConditionGroupResource::collection($this->resource->conditionGroups),
            'target_products' => ProductResource::collection($this->resource->products),
            'target_sets' => ProductSetResource::collection($this->resource->productSets),
            'target_shipping_methods' => ShippingMethodResource::collection($this->resource->shippingMethods),
        ];
    }
}
