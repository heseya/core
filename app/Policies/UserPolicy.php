<?php

namespace App\Policies;

use App\Exceptions\TFAException;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    use HandlesAuthorization;

    public function removeUserTFA(User|App $user, User $selected_user): Response
    {
        if ($user->getAuthIdentifier() === $selected_user->getAuthIdentifier()) {
            throw new TFAException('You cannot remove 2FA yourself in this way.');
        }

        return Response::allow();
    }
}
