<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class SettingResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'name' => $this->resource->name,
            'value' => $this->resource->value,
            'public' => $this->resource->public,
            'permanent' => $this->resource->permanent,
        ];
    }
}
