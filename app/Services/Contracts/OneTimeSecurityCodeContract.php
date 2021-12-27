<?php

namespace App\Services\Contracts;

use App\Models\User;

interface OneTimeSecurityCodeContract
{
    public function generateOneTimeSecurityCode(User $user, int $expires_time): string;
}
