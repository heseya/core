<?php

namespace App\Policies;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use App\Models\App;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    use HandlesAuthorization;

    public function removeUserTFA(App|User $user, User $selected_user): Response
    {
        if ($user->getAuthIdentifier() === $selected_user->getAuthIdentifier()) {
            throw new ClientException(Exceptions::CLIENT_TFA_CANNOT_REMOVE);
        }

        return Response::allow();
    }
}
