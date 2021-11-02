<?php

namespace App\Channels;

use App\Notifications\WebHookNotification;
use Spatie\WebhookServer\WebhookCall;

class WebHookChannel
{
    public function send($notifiable, WebHookNotification $notification)
    {
        $data = $notification->toWebHook($notifiable);

        $webhook = WebhookCall::create()
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
