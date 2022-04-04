<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class StatusResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'color' => $this->resource->color,
            'cancel' => $this->resource->cancel,
            'description' => $this->resource->description,
            'hidden' => $this->resource->hidden,
            'no_notifications' => $this->resource->no_notifications,
        ];
    }
}
