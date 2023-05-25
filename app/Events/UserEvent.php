<?php

namespace App\Events;

use App\Http\Resources\UserResource;
use App\Models\User;

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
        return UserResource::make($this->user)->resolve();
    }

    public function getDataType(): string
    {
        return $this->getModelClass($this->user);
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
