<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class UserLoginAttemptResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'ip' => $this->resource->ip,
            'user_agent' => $this->resource->user_agent,
            'date' => $this->resource->created_at,
            'user' => UserResource::make($this->resource->user),
        ];
    }
}
