<?php

namespace App\Listeners;

use App\Enums\UserRegisteredTemplate;
use App\Events\UserCreated;
use App\Mail\UserRegistered;
use App\Traits\GetLocale;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class UserCreatedListener
{
    use GetLocale;

    public function handle(UserCreated $event): void
    {
        $user = $event->getUser();

        try {
            if (Config::get('client.mails')) {
                if ($user->metadataPersonal->where('name', 'partner')->first()?->value) {
                    Mail::to($user->email)
                        ->locale($this->getLocaleFromRequest())
                        ->send(new UserRegistered(
                            $user->name ?? $user->email,
                            UserRegisteredTemplate::CLIENT_PARTNER,
                        ));
                }
                if (!$user->metadataPersonal->where('name', 'partner')->first()?->value || $user->metadataPersonal->where('name', 'insider')->first()?->value) {
                    Mail::to($user->email)
                        ->locale($this->getLocaleFromRequest())
                        ->send(new UserRegistered(
                            $user->name ?? $user->email,
                            UserRegisteredTemplate::CLIENT_INSIDER,
                        ));
                }
            } else {
                Mail::to($user->email)
                    ->locale($this->getLocaleFromRequest())
                    ->send(new UserRegistered(
                        $user->name ?? $user->email,
                    ));
            }
        } catch (Exception) {
            Log::error('User registration notification not send for user with id: ' . $user->getKey());
        }
    }
}
