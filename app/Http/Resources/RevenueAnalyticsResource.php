<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class SchemaResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'total' => $this['total'],
        ];
    }
}
