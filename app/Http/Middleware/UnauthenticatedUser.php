<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Support\Facades\Auth;

class UnauthenticatedUser extends Middleware
{
    /**
     */
    public function handle($request, Closure $next, ...$guards): mixed
    {
        if (!Auth::check()) {
            Auth::setUser(
                User::where('name', 'Unauthenticated')->firstOrFail(),
            );

            dd(Auth::user()->getAllPermissions());
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
