<?php

namespace App\Http\Resources;

use App\Models\OrderSchema;
use Domain\Order\Dtos\OrderPriceDto;
use Illuminate\Http\Request;

/**
 * @property OrderSchema $resource
 */
class OrderSchemaResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'value' => $this->resource->value,
            'price' => OrderPriceDto::from($this->resource->price, $this->resource->orderProduct->vat_rate, true),
            'price_initial' => OrderPriceDto::from($this->resource->price, $this->resource->orderProduct->vat_rate, true),
        ];
    }
}
