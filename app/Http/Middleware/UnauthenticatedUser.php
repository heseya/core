<?php

namespace App\Http\Middleware;

use App\Models\Role;
use App\Models\User;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Support\Facades\Auth;

class UnauthenticatedUser extends Middleware
{
    /**
     * @throws AuthenticationException
     */
    public function handle($request, Closure $next, ...$guards): mixed
    {
        if ($request->hasHeader('Authorization') && !Auth::check()) {
            throw new AuthenticationException();
        }

        if (!Auth::check()) {
            $user = new User;
            $user->id = 'x';
            $user->name = 'Unauthenticated';

            $roles = Role::where('name', 'Unauthenticated')->get();
            $user->setRelation('roles', $roles);

            Auth::setUser($user);
        }

        return $next($request);
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @return null
     */
    protected function redirectTo($request)
    {
        return null;
    }
}
