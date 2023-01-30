<?php

namespace App\Http\Resources;

use App\Traits\MetadataResource;
use Illuminate\Http\Request;

class StatusResource extends Resource
{
    use MetadataResource;

    public function base(Request $request): array
    {
        return array_merge([
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'color' => $this->resource->color,
            'cancel' => $this->resource->cancel,
            'description' => $this->resource->description,
            'hidden' => $this->resource->hidden,
            'no_notifications' => $this->resource->no_notifications,
        ], $this->metadataResource('statuses.show_metadata_private'));
    }
}
