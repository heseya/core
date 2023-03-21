<?php

namespace App\Http\Resources\App;

use App\Http\Resources\Resource;
use App\Traits\MetadataResource;
use Illuminate\Http\Request;

class AppResource extends Resource
{
    use MetadataResource;

    public function base(Request $request): array
    {
        return array_merge([
            'id' => $this->resource->getKey(),
            'url' => $this->resource->url,
            'microfrontend_url' => $this->resource->microfrontend_url,
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'version' => $this->resource->version,
            'description' => $this->resource->description,
            'icon' => $this->resource->icon,
            'author' => $this->resource->author,
        ], $this->metadataResource('apps.show_metadata_private'));
    }

    public function view(Request $request): array
    {
        return [
            'permissions' => $this->resource->getPermissionNames()->sort()->values(),
        ];
    }
}
