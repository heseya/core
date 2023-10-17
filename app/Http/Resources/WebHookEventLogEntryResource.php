<?php

namespace App\Http\Resources;

use App\Models\WebHookEventLogEntry;
use Illuminate\Http\Request;

/**
 * @property WebHookEventLogEntry $resource
 */
final class WebHookEventLogEntryResource extends Resource
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
            'event' => $this->resource->event ?? $this->resource->payload['event'] ?? null,
            'response' => $this->resource->response,
        ];
    }
}
