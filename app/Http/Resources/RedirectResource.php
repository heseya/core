<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class RedirectResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'source_url' => $this->resource->source_url,
            'target_url' => $this->resource->target_url,
            'type' => $this->resource->type->value,
            'enabled' => $this->resource->enabled,
        ];
    }
}
