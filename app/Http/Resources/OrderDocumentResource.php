<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class OrderDocumentResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->pivot->id,
            'type' => $this->pivot->type,
            'name' => $this->pivot->name,
        ];
    }
}
