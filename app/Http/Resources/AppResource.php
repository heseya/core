<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class AppResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'url' => $this->resource->url,
            'microfrontend_url' => $this->resource->microfrontend_url,
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'version' => $this->resource->version,
            'description' => $this->resource->description,
            'icon' => $this->resource->icon,
            'author' => $this->resource->author,
        ];
    }

    public function view(Request $request): array
    {
        return [
            'permissions' => $this->resource->getPermissionNames()->sort()->values(),
        ];
    }
}
