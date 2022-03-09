<?php

namespace App\Http\Resources;

use App\Traits\MetadataResource;
use Illuminate\Http\Request;

class OptionResource extends Resource
{
    use MetadataResource;

    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'price' => $this->price,
            'disabled' => $this->disabled,
            'available' => $this->available,
            'items' => ItemResource::collection($this->items),
        ];
    }

    public function view(Request $request): array
    {
        return $this->metadataResource('options');
    }
}
