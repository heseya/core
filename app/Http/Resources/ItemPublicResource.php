<?php

namespace App\Http\Resources;

use App\Models\Item;
use Illuminate\Http\Request;

/**
 * @property Item $resource
 */
class ItemPublicResource extends Resource
{
    /**
     * @return array<string, mixed>
     */
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'sku' => $this->resource->sku,
            'quantity' => $this->resource->quantity_real,
        ];
    }
}
