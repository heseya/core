<?php

namespace App\Listeners;

use App\Events\WebHookEvent;
use App\Models\WebHook;
use App\Notifications\WebHookNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;

class WebHookEventListener implements ShouldQueue
{
    public function handle(WebHookEvent $event): void
    {
        $query = WebHook::query()->whereJsonContains('events', $event->getEvent());

        if ($event->isHidden()) {
            $query->where('with_hidden', true);
        }

        $webHooks = $query->get();

        if ($webHooks->count() <= 0) {
            return;
        }

        if ($event->isEncrypted()) {
            $payload = $event->getData();
            $payload['data'] = $this->encryptData($payload['data']);
        }

        Notification::send($webHooks, new WebHookNotification(
            $payload ?? $event->getData(),
            $event->getIssuer(),
        ));
    }

    private function encryptData(array|string $data): string
    {
        /** @var string $data */
        $data = json_encode($data);
        /** @var string $cipher */
        $cipher = Config::get('webhook.cipher');

        /** @var int $ivLen */
        $ivLen = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivLen);

        return base64_encode($iv . openssl_encrypt(
            $data,
            $cipher,
            Config::get('webhook.key'),
            OPENSSL_RAW_DATA,
            $iv,
        ));
    }

    public function viaQueue(): string
    {
        return config('webhook-server.queue');
    }
}
