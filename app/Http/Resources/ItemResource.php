<?php

namespace App\Http\Resources;

use App\Traits\MetadataResource;
use Illuminate\Http\Request;

class ItemResource extends Resource
{
    use MetadataResource;

    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'sku' => $this->sku,
            'quantity' => $this->getQuantity($request->input('day')),
        ];
    }

    public function view(Request $request): array
    {
        return $this->metadataResource('items');
    }
}
