<?php

namespace App\Listeners;

use App\Events\UserCreated;
use App\Notifications\UserRegistered;
use Exception;
use Illuminate\Support\Facades\Log;

class UserCreatedListener
{
    public function handle(UserCreated $event): void
    {
        $user = $event->getUser();

        try {
            $user->notify(new UserRegistered());
        } catch (Exception) {
            Log::error('User registration notification not send for user with id: ' . $user->getKey());
        }
    }
}
