<?php

namespace App\Policies;

use App\Models\App;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AuthenticatedPolicy
{
    use HandlesAuthorization;

    public function authenticated(User|App $user): bool
    {
        return $user->getAuthIdentifier() !== null;
    }
}
