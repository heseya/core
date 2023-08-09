<?php

namespace App\Http\Resources;

use App\Traits\MetadataResource;
use Domain\Order\Resources\OrderStatusResource;
use Illuminate\Http\Request;

class OrderShortResource extends Resource
{
    use MetadataResource;

    public function base(Request $request): array
    {
        return array_merge([
            'id' => $this->resource->getKey(),
            'code' => $this->resource->code,
            'status' => OrderStatusResource::make($this->resource->status),
            'paid' => $this->resource->paid,
            'summary' => $this->resource->summary,
            'created_at' => $this->resource->created_at,
        ], $this->metadataResource('orders.show_metadata_private'));
    }
}
