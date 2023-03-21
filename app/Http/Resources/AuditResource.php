<?php

namespace App\Http\Resources;

use App\Http\Resources\App\AppResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuditResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'event' => $this->resource->event,
            'created_at' => $this->resource->created_at,
            'old_values' => $this->resource->old_values,
            'new_values' => $this->resource->new_values,
            'issuer_type' => Str::of($this->resource->user_type)->after('App\\Models\\')->lower(),
            'issuer' => $this->resource->user instanceof User ?
                UserResource::make($this->resource->user)->baseOnly() :
                AppResource::make($this->resource->user)->baseOnly(),
        ];
    }
}
