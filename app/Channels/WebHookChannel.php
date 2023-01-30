<?php

namespace App\Channels;

use App\Notifications\WebHookNotification;
use Spatie\WebhookServer\WebhookCall;

class WebHookChannel
{
    public function send(mixed $notifiable, WebHookNotification $notification): void
    {
        $data = $notification->toWebHook($notifiable);

        $webhook = WebhookCall::create()
            ->meta([
                'web_hook_id' => $notifiable->getKey(),
                'event' => $data['event'] ?? null,
                'triggered_at' => $data['triggered_at'] ?? null,
            ])
            ->throwExceptionOnFailure()
            ->url($notifiable->url)
            ->payload($data);

        $secret = $notifiable->secret;

        if ($secret !== null) {
            $webhook
                ->signUsing(WebHookSigner::class)
                ->useSecret($secret);
        } else {
            $webhook->doNotSign();
        }

        $webhook->dispatch();
    }
}
