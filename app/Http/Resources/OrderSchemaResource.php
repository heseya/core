<?php

namespace App\Http\Resources;

use App\Traits\MetadataResource;
use Illuminate\Http\Request;

class OrderSchemaResource extends Resource
{
    use MetadataResource;

    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'value' => $this->value,
            'price' => $this->price,
        ];
    }

    public function view(Request $request): array
    {
        return $this->metadataResource();
    }
}
