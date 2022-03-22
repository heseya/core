<?php

namespace App\Http\Resources;

use App\Traits\MetadataResource;
use Illuminate\Http\Request;

class OptionResource extends Resource
{
    use MetadataResource;

    public function base(Request $request): array
    {
        return array_merge([
            'id' => $this->getKey(),
            'name' => $this->name,
            'price' => $this->price,
            'disabled' => $this->disabled,
            'available' => $this->available,
            'items' => ItemPublicResource::collection($this->items),
        ], $this->metadataResource('options'));
    }
}
