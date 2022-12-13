<?php

namespace App\Http\Resources;

use App\Traits\MetadataResource;
use Illuminate\Http\Request;

class ItemResource extends Resource
{
    use MetadataResource;

    public function base(Request $request): array
    {
        return array_merge([
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'sku' => $this->resource->sku,
            'quantity' => (float) $this->resource->getQuantity($request->input('day')),
            'unlimited_stock_shipping_time' => $this->resource->unlimited_stock_shipping_time,
            'unlimited_stock_shipping_date' => $this->resource->unlimited_stock_shipping_date,
            'shipping_time' => $this->resource->shipping_time,
            'shipping_date' => $this->resource->shipping_date,
            'availability' => ItemDepositResource::collection($this->resource->groupedDeposits),
        ], $this->metadataResource('items.show_metadata_private'));
    }
}
