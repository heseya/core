<?php

namespace App\Http\Resources;

use App\Models\DiscountCondition;
use Domain\Organization\Resources\OrganizationResource;
use Domain\ProductSet\Resources\ProductSetResource;
use Illuminate\Http\Request;

/**
 * @property DiscountCondition $resource
 */
class ConditionResource extends Resource
{
    public function base(Request $request): array
    {
        $value = $this->resource->value;

        if (array_key_exists('users', $value)) {
            $value['users'] = UserResource::collection($this->resource->users);
        }

        if (array_key_exists('roles', $value)) {
            $value['roles'] = RoleResource::collection($this->resource->roles);
        }

        if (array_key_exists('products', $value)) {
            $value['products'] = ProductResource::collection($this->resource->products);
        }

        if (array_key_exists('product_sets', $value)) {
            $value['product_sets'] = ProductSetResource::collection($this->resource->productSets);
        }

        if (array_key_exists('organizations', $value)) {
            $value['organizations'] = OrganizationResource::collection($this->resource->organizations);
        }

        if (array_key_exists('min_values', $value)) {
            if (empty($value['min_values'])) {
                $value['min_values'] = null;
            } else {
                $value['min_values'] = PriceResource::collection($this->resource->pricesMin);
            }
        }

        if (array_key_exists('max_values', $value)) {
            if (empty($value['max_values'])) {
                $value['max_values'] = null;
            } else {
                $value['max_values'] = PriceResource::collection($this->resource->pricesMax);
            }
        }

        return [
            'id' => $this->resource->getKey(),
            'type' => $this->resource->type,
        ] + $value;
    }
}
