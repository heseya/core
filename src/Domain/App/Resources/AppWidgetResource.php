<?php

declare(strict_types=1);

namespace Domain\App\Resources;

use App\Http\Resources\AppResource;
use App\Http\Resources\Resource;
use Domain\App\Models\AppWidget;
use Illuminate\Http\Request;

/**
 * @property AppWidget $resource
 */
final class AppWidgetResource extends Resource
{
    /**
     * @return array<string, mixed>
     */
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'app' => AppResource::make($this->resource->app),
            'url' => $this->resource->url,
            'name' => $this->resource->name,
            'section' => $this->resource->section,
            'permissions' => $this->resource->getPermissionNames()->sort()->values(),
        ];
    }
}
