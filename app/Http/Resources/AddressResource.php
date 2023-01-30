<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class AddressResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'address' => $this->resource->address,
            'vat' => $this->resource->vat,
            'zip' => $this->resource->zip,
            'city' => $this->resource->city,
            'country' => $this->resource->country,
            'country_name' => $this->resource->country_name,
            'phone' => $this->resource->phone,
        ];
    }
}
