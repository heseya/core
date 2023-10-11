<?php

namespace App\Listeners;

use App\Enums\UserRegisteredTemplate;
use App\Events\UserCreated;
use App\Notifications\UserRegistered;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class UserCreatedListener
{
    public function handle(UserCreated $event): void
    {
        $user = $event->getUser();

        try {
            if (Config::get('client.mails')) {
                if ($user->metadataPersonal->where('name', 'partner')->first()?->value) {
                    $user->notify(new UserRegistered(UserRegisteredTemplate::CLIENT_PARTNER));
                }
                if (!$user->metadataPersonal->where('name', 'partner')->first()?->value || $user->metadataPersonal->where('name', 'insider')->first()?->value) {
                    $user->notify(new UserRegistered(UserRegisteredTemplate::CLIENT_INSIDER));
                }
            } else {
                $user->notify(new UserRegistered());
            }
        } catch (Exception) {
            Log::error('User registration notification not send for user with id: ' . $user->getKey());
        }
    }
}
