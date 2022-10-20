<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ProductItemResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'required_quantity' => $this->resource->pivot->required_quantity,
        ];
    }
}
