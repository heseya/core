<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AuthProviderResource extends Resource
{
    public function base(Request $request): array
    {
        if ($this->resource) {
            $client = [];
            if (Gate::allows('auth.providers.manage')) {
                $client = [
                    'client_id' => $this->resource->client_id,
                    'client_secret' => $this->resource->client_secret,
                ];
            }

            return array_merge([
                'id' => $this->resource->getKey(),
                'key' => $this->resource->key,
                'active' => $this->resource->active,
            ], $client);
        }
        return [];
    }
}
