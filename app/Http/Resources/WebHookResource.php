<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class WebHookResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'url' => $this->resource->url,
            'secret' => $this->resource->secret,
            'with_issuer' => $this->resource->with_issuer,
            'with_hidden' => $this->resource->with_hidden,
            'events' => $this->resource->events,
        ];
    }
}
