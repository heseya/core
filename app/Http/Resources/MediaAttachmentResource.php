<?php

namespace App\Http\Resources;

use App\Models\MediaAttachment;
use Illuminate\Http\Request;

/**
 * @property MediaAttachment $resource
 */
class MediaAttachmentResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'type' => $this->resource->type,
            'description' => $this->resource->description,
            'visibility' => $this->resource->visibility,
            'media' => new MediaResource($this->resource->media),
        ];
    }
}
