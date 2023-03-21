<?php

namespace App\Http\Resources\App;

use App\Http\Resources\PermissionResource;
use App\Http\Resources\Resource;
use Illuminate\Http\Request;

class AppWidgetResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'url' => $this->resource->url,
            'section' => $this->resource->section,
            'permissions' => PermissionResource::collection($this->resource->permissions),
        ];
    }
}
