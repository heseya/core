<?php

namespace App\Events;

use App\Http\Resources\UserResource;
use App\Models\User;

class PasswordReset extends WebHookEvent
{
    protected User $user;
    protected string $recoveryUrl;

    public function __construct(User $user, string $recoveryUrl)
    {
        parent::__construct();
        $this->user = $user;
        $this->recoveryUrl = $recoveryUrl;
        $this->encrypted = true;
    }

    public function getDataContent(): array
    {
        return [
            'user' => UserResource::make($this->user),
            'recovery_url' => $this->recoveryUrl,
        ];
    }

    public function getDataType(): string
    {
        return 'PasswordRecovery';
    }
}
