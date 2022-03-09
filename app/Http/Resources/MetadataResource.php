<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class MetadataResource extends Resource
{
    public function base(Request $request): array
    {
        $resource = [];

        $this->resource->map(function ($metadata) use (&$resource) {
            $resource[$metadata->name] = $metadata->value;
        });

        return $resource;
    }
}
