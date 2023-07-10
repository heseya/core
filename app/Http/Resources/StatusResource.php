<?php

namespace App\Http\Resources;

use App\Traits\GetAllTranslations;
use App\Traits\MetadataResource;
use Illuminate\Http\Request;

class StatusResource extends Resource
{
    use GetAllTranslations;
    use MetadataResource;

    public function base(Request $request): array
    {
        $data = [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'color' => $this->resource->color,
            'cancel' => $this->resource->cancel,
            'description' => $this->resource->description,
            'hidden' => $this->resource->hidden,
            'no_notifications' => $this->resource->no_notifications,
        ];

        return array_merge(
            $data,
            $request->has('translations') ? $this->getAllTranslations() : [],
            $this->metadataResource('statuses.show_metadata_private'),
        );
    }
}
