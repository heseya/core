<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class SavedAddressResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'default' => $this->resource->default,
            'name' => $this->resource->name,
            'address' => AddressResource::make($this->resource->address),
        ];
    }
}
