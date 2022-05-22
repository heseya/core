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
        $webHooks = WebHook::whereJsonContains('events', $event->getEvent());

        if ($event->isHidden()) {
            $webHooks->where('with_hidden', true);
        }

        $webHooks = $webHooks->get();

        if ($webHooks->count() > 0) {
            Notification::send($webHooks, new WebHookNotification(
                $event->getData(),
                $event->getIssuer(),
            ));
        }
    }
}
