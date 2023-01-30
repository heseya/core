<?php

namespace App\Events;

use App\Http\Resources\UserResource;
use App\Models\User;

class TfaRecoveryCodesChanged extends WebHookEvent
{
    protected User $user;

    public function __construct(User $user)
    {
        parent::__construct();
        $this->user = $user;
        $this->encrypted = true;
    }

    public function getDataContent(): array
    {
        return UserResource::make($this->user)->resolve();
    }

    public function getDataType(): string
    {
        return $this->getModelClass($this->user);
    }
}
