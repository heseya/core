<?php

namespace App\Policies;

use App\Models\User;
use Domain\App\Models\App;
use Illuminate\Auth\Access\HandlesAuthorization;

class AuthenticatedPolicy
{
    use HandlesAuthorization;

    public function authenticated(App|User $user): bool
    {
        return $user->getAuthIdentifier() !== null;
    }
}
