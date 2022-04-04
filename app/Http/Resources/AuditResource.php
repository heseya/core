<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class AuditResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'event' => $this->resource->event,
            'created_at' => $this->resource->created_at,
            'old_values' => $this->resource->old_values,
            'new_values' => $this->resource->new_values,
            'user' => UserResource::make($this->resource->user)->baseOnly(),
        ];
    }
}
