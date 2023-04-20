<?php

namespace App\Http\Resources;

use App\Models\Page;
use Illuminate\Http\Request;

/**
 * @property Page $resource
 */
class PageShortResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'slug' => $this->resource->slug,
            'name' => $this->resource->name,
        ];
    }
}
