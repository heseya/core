<?php

namespace App\Http\Middleware;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class UserAccessRestrict
{
    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->user() instanceof User) {
            throw new ClientException(Exceptions::CLIENT_USERS_NO_ACCESS);
        }

        return $next($request);
    }
}
