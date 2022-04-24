<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PaymentMethodResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'icon' => $this->resource->icon,
            'alias' => $this->resource->alias,
            'public' => $this->resource->public,
        ];
    }
}
