<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ItemPublicResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'sku' => $this->sku,
        ];
    }
}
