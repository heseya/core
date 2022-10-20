<?php

namespace App\Listeners;

use App\Models\WebHookEventLogEntry;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Carbon;
use Spatie\WebhookServer\Events\FinalWebhookCallFailedEvent;

class WebHookFailedListener implements ShouldQueue
{
    public function handle(FinalWebhookCallFailedEvent $event): void
    {
        WebHookEventLogEntry::create([
            'id' => $event->uuid,
            'web_hook_id' => array_key_exists('web_hook_id', $event->meta)
                ? $event->meta['web_hook_id'] : null,
            'triggered_at' => array_key_exists('triggered_at', $event->meta)
                ? $event->meta['triggered_at'] : Carbon::now(),
            'url' => $event->webhookUrl,
            'status_code' => $event->response?->getStatusCode(),
            'payload' => json_encode($event->payload),
            'response' => null, // TODO: $event->response?->getBody() when guzzle fix this
        ]);
    }
}
