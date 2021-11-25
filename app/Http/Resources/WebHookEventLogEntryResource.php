<?php

namespace App\Http\Resources;

use App\Http\Resources\Swagger\WebHookEventLogEntryResourceSwagger;
use Illuminate\Http\Request;

class WebHookEventLogEntryResource extends Resource implements WebHookEventLogEntryResourceSwagger
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'url' => $this->url,
            'triggered_at' => $this->triggered_at,
            'status_code' => $this->status_code,
        ];
    }
}
