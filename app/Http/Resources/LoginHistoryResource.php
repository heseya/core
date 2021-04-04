<?php

namespace App\Http\Resources;

use Jenssegers\Agent\Agent;

class LoginHistoryResource extends Resource
{
    public function base($request): array
    {
        $agent = new Agent();
        $agent->setUserAgent($this->user_agent);

        return [
            'device' => $agent->device() === false ? null : $agent->device(),
            'platform' => $agent->platform() === false ? null : $agent->platform(),
            'browser' => $agent->browser() === false ? null : $agent->browser(),
            'ip' => $this->ip,
            'revoked' => (bool) $this->revoked,
            'created_at' => $this->created_at,
            'expires_at' => $this->expires_at->format('Y-d-m H:m:s'),
        ];
    }
}
