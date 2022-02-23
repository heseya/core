<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class WebHookEventLogEntryResource extends Resource
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
