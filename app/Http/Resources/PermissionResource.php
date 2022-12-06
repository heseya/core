<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PermissionResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'display_name' => $this->resource->display_name,
            'description' => $this->resource->description,
            'assignable' => Auth::user()?->can($this->resource->name),
        ];
    }
}
