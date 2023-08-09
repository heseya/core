<?php

namespace App\Http\Resources;

use Domain\ProductSet\Resources\ProductSetResource;
use Illuminate\Http\Request;

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

        return [
            'id' => $this->resource->getKey(),
            'type' => $this->resource->type,
        ] + $value;
    }
}
