<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class WebHookEventLogEntryResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'url' => $this->resource->url,
            'triggered_at' => $this->resource->triggered_at,
            'status_code' => $this->resource->status_code,
            'web_hook' => WebHookResource::make($this->resource->webHook),
            'payload' => $this->resource->payload,
            'response' => $this->resource->response,
        ];
    }
}
