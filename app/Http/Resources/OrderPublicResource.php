<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class OrderPublicResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'code' => $this->resource->code,
            'status' => StatusResource::make($this->resource->status),
            'paid' => $this->resource->paid,
            'payable' => $this->resource->payable,
            'summary' => $this->resource->summary,
            'shipping_method_id' => $this->resource->shipping_method_id,
            'created_at' => $this->resource->created_at,
        ];
    }
}
