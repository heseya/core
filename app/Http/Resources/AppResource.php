<?php

namespace App\Http\Resources;

use App\Http\Resources\Swagger\AppResourceSwagger;
use Illuminate\Http\Request;

class AppResource extends Resource implements AppResourceSwagger
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'url' => $this->url,
            'microfrontend_url' => $this->microfrontend_url,
            'name' => $this->name,
            'slug' => $this->slug,
            'version' => $this->version,
            'description' => $this->description,
            'icon' => $this->icon,
            'author' => $this->author,
        ];
    }

    public function view(Request $request): array
    {
        return [
            'permissions' => $this->getPermissionNames()->sort()->values(),
        ];
    }
}
