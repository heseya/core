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

    public function getDataContent(): array
    {
        return $this->user->toArray();
    }

    public function getDataType(): string
    {
        return $this->getModelClass($this->user);
    }
}
