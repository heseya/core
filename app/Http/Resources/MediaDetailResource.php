<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class MediaDetailResource extends MediaResource
{
    public function base(Request $request): array
    {
        return parent::base($request) + [
            'relations_count' => $this->resource->relationsCount(),
        ];
    }
}
