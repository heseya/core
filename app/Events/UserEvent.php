<?php

namespace App\Events;

use App\Models\User;

abstract class UserEvent extends WebHookEvent
{
    private User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function getData(): array
    {
        return $this->user->toArray();
    }
}
