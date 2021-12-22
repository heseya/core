<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class WebHookResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'url' => $this->url,
            'secret' => $this->secret,
            'with_issuer' => $this->with_issuer,
            'with_hidden' => $this->with_hidden,
            'events' => $this->events,
            'logs' => $this->logs,
        ];
    }
}
