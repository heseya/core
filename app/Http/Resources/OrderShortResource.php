<?php

namespace App\Http\Resources;

use App\Models\Order;
use App\Traits\MetadataResource;
use Domain\Order\Dtos\OrderPriceDto;
use Domain\Order\Resources\OrderStatusResource;
use Illuminate\Http\Request;

/**
 * @property Order $resource
 */
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
            'summary' => OrderPriceDto::from($this->resource->summary, $this->resource->vat_rate),
            'created_at' => $this->resource->created_at,
        ], $this->metadataResource('orders.show_metadata_private'));
    }
}
