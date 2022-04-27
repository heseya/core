<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class CountryResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'code' => $this->resource->code,
            'name' => $this->resource->name,
        ];
    }
}
