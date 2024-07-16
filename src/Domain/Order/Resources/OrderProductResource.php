<?php

declare(strict_types=1);

namespace Domain\Order\Resources;

use App\Http\Resources\OrderProductUrlResource;
use App\Http\Resources\OrderSchemaResource;
use App\Http\Resources\Resource;
use App\Models\OrderProduct;
use Illuminate\Http\Request;

/**
 * @property OrderProduct $resource
 */
final class OrderProductResource extends Resource
{
    /**
     * @return array<string, mixed>
     */
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'quantity' => (float) $this->resource->quantity,
            'price' => $this->resource->price->getAmount(),
            'price_initial' => $this->resource->price_initial->getAmount(),
            'vat_rate' => $this->resource->vat_rate,
            'schemas' => OrderSchemaResource::collection($this->resource->schemas),
            'deposits' => OrderDepositResource::collection($this->resource->deposits),
            'discounts' => OrderDiscountResource::collection($this->resource->discounts),
            'shipping_digital' => $this->resource->shipping_digital,
            'is_delivered' => $this->resource->is_delivered,
            'urls' => OrderProductUrlResource::collection($this->resource->urls),
            'product' => $this->resource->product
                ? OrderProductDetailsResource::make($this->resource->product)
                : null,
        ];
    }
}
