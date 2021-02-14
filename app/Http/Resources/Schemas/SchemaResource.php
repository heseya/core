<?php

namespace App\Http\Resources\Schemas;

use App\Http\Resources\Resource;
use Illuminate\Http\Request;

class SchemaResource extends Resource
{
    public function base(Request $request): array
    {
        return $this->resource->toResource()->toArray($request);
    }
}
