<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class OrderDocumentResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'type' => $this->resource->type,
            'name' => $this->resource->name,
        ];
    }
}
