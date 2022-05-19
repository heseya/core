<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class MetadataResource extends Resource
{
    public function base(Request $request): array
    {
        $resource = [];

        $this->resource->map(function ($metadata) use (&$resource): void {
            $resource[$metadata->name] = $metadata->value;
        });

        return $resource;
    }

    /**
     * Transform the resource into json.
     * Rewrite to send empty object instead of empty array.
     */
    public function toJson($options = 0): string
    {
        if ($this->resource->empty() === []) {
            return '{}';
        }

        return parent::toJson($options);
    }
}
