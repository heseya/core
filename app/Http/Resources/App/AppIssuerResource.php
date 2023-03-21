<?php

namespace App\Http\Resources\App;

use App\Http\Resources\Resource;
use Illuminate\Http\Request;

class AppIssuerResource extends Resource
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
}
