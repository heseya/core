<?php

namespace App\Http\Resources;

use App\Traits\MetadataResource;
use Illuminate\Http\Request;

class SaleResource extends Resource
{
    use MetadataResource;

    public function base(Request $request): array
    {
        if (isset($this->resource->pivot)) {
            // @phpstan-ignore-next-line
            $this->resource->type = $this->resource->pivot->type;
            // @phpstan-ignore-next-line
            $this->resource->discount = $this->resource->pivot->discount;
        }

        return array_merge([
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'value' => $this->resource->value,
            'type' => $this->resource->type,
            'priority' => $this->resource->priority,
            'uses' => $this->resource->uses,
            'condition_groups' => ConditionGroupResource::collection($this->resource->conditionGroups),
            'target_type' => $this->resource->target_type,
            'target_products' => ProductResource::collection($this->resource->products),
            'target_sets' => ProductSetResource::collection($this->resource->productSets),
            'target_shipping_methods' => ShippingMethodResource::collection($this->resource->shippingMethods),
            'target_is_allow_list' => $this->resource->target_is_allow_list,
        ], $this->metadataResource('sales.show_metadata_private'));
    }
}
