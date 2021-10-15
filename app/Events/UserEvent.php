<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Support\Str;

abstract class UserEvent extends WebHookEvent
{
    protected User $user;

    public function __construct(User $user)
    {
        parent::__construct();
        $this->user = $user;
    }

    public function getData(): array
    {
        return [
            'data' => $this->user->toArray(),
            'data_type' => Str::remove('App\\Models\\', $this->user::class),
            'event' => Str::remove('App\\Events\\', $this::class),
            'triggered_at' => $this->triggered_at,
        ];
    }
}
