<?php

namespace App\Http\Resources;

use App\Http\Resources\Swagger\PermissionResourceSwagger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PermissionResource extends Resource implements PermissionResourceSwagger
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
