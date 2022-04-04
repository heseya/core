<?php

namespace App\Http\Resources;

class CountryResource extends Resource
{
    public function base($request): array
    {
        return [
            'code' => $this->resource->code,
            'name' => $this->resource->name,
        ];
    }
}
