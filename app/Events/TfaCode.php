<?php

namespace App\Events;

use App\Http\Resources\UserResource;
use App\Models\User;

abstract class TfaCode extends WebHookEvent
{
    protected User $user;
    protected string $securityCode;

    public function __construct(User $user, string $securityCode)
    {
        parent::__construct();
        $this->user = $user;
        $this->securityCode = $securityCode;
        $this->encrypted = true;
    }

    public function getDataContent(): array
    {
        return [
            'user' => UserResource::make($this->user)->resolve(),
            'security_code' => $this->securityCode,
        ];
    }

    public function getDataType(): string
    {
        return 'TfaCode';
    }
}
