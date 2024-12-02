<?php

declare(strict_types=1);

namespace Domain\Manufacturer\Resources;

use App\Http\Resources\AddressResource;
use App\Http\Resources\ProductWithoutSalesResource;
use App\Http\Resources\Resource;
use Illuminate\Http\Request;

final class ManufacturerResource extends Resource
{
    /**
     * @return array<string, mixed>
     */
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'first_name' => $this->resource->first_name,
            'last_name' => $this->resource->last_name,
            'email' => $this->resource->email,
            'address' => $this->resource->address ? AddressResource::make($this->resource->address) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function view(Request $request): array
    {
        return [
            'product_ids' => ProductWithoutSalesResource::collection($this->resource->productIds()),
        ];
    }
}
