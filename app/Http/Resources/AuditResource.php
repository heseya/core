<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class AuditResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'event' => $this->event,
            'created_at' => $this->created_at,
            'old_values' => $this->old_values,
            'new_values' => $this->new_values,
            'user' => UserResource::make($this->user)->baseOnly(),
        ];
    }
}
