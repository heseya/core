<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class OrderDocumentResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'name' => $this->name,
        ];
    }
}
