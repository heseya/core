<?php

namespace App\Http\Resources;

use App\Models\OrderProduct;
use Domain\Order\Resources\OrderDepositResource;
use Domain\ProductSet\Resources\ProductSetResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * @property OrderProduct $resource
 */
class OrderProductResource extends Resource
{
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
                ? (ProductWithAttributesResource::make($this->resource->product)->baseOnly()->toArray($request) + [
                    'sets' => ProductSetResource::collection(
                        Gate::denies('product_sets.show_hidden')
                            ? $this->resource->product->sets->where('public', true)
                            : $this->resource->product->sets,
                    ),
                ])
                : null,
        ];
    }
}
