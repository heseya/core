<?php

namespace App\Listeners;

use App\Models\WebHookEventLogEntry;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Spatie\WebhookServer\Events\FinalWebhookCallFailedEvent;

class WebHookFailedListener implements ShouldQueue
{
    public function handle(FinalWebhookCallFailedEvent $event): void
    {
        WebHookEventLogEntry::query()->create([
            'id' => $event->uuid,
            'event' => is_array($event->payload) ? Arr::get($event->payload, 'event') : null,
            'web_hook_id' => Arr::get($event->meta, 'web_hook_id'),
            'triggered_at' => Arr::get($event->meta, 'triggered_at', Carbon::now()),
            'url' => $event->webhookUrl,
            'status_code' => $event->response?->getStatusCode(),
            'payload' => json_encode($event->payload),
            'response' => null, // TODO: $event->response?->getBody() when guzzle fix this
        ]);
    }
}
