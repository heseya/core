<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PermissionResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'display_name' => $this->display_name,
            'description' => $this->description,
            'assignable' => Auth::user()->can($this->name),
        ];
    }
}
