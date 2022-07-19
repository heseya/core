<?php

namespace App\Events;

use App\Http\Resources\UserLoginAttemptResource;
use App\Models\UserLoginAttempt;

abstract class LocalizedLoginAttempt extends WebHookEvent
{
    protected UserLoginAttempt $attempt;

    public function __construct(UserLoginAttempt $attempt)
    {
        parent::__construct();
        $this->attempt = $attempt;
        $this->encrypted = true;
    }

    public function getDataContent(): array
    {
        return UserLoginAttemptResource::make($this->attempt)->resolve();
    }

    public function getDataType(): string
    {
        return 'LocalizedLoginAttempt';
    }
}
