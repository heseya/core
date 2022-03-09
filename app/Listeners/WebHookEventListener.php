<?php

namespace App\Listeners;

use App\Events\WebHookEvent;
use App\Models\WebHook;
use App\Notifications\WebHookNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class WebHookEventListener implements ShouldQueue
{
    public function handle(WebHookEvent $event): void
    {
        $event_data = $event->getData();
        $issuer = $event->getIssuer();

        if ($event->isHidden()) {
            $web_hooks = WebHook::whereJsonContains('events', $event_data['event'])
                ->where('with_hidden', '=', true)
                ->get();
        } else {
            $web_hooks = WebHook::whereJsonContains('events', $event_data['event'])
                ->get();
        }

        Notification::send($web_hooks, new WebHookNotification($event_data, $issuer));
    }
}
