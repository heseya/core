<?php

namespace App\Http\Middleware;

use App\Enums\RoleType;
use App\Enums\TokenType;
use App\Models\App;
use App\Models\Role;
use App\Models\User;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class Authenticate extends Middleware
{
    /**
     * @throws AuthenticationException
     */
    public function handle($request, Closure $next, ...$guards): mixed
    {
        if (!Auth::check()) {
            if ($request->hasHeader('Authorization')) {
                Config::set('auth.providers.users.model', App::class);
                Auth::forgetGuards();

                if (!Auth::check()) {
                    throw new AuthenticationException();
                }
            } else {
                $user = User::make([
                    'name' => 'Unauthenticated',
                ]);

                $roles = Role::where('type', RoleType::UNAUTHENTICATED)->get();
                $user->setRelation('roles', $roles);
                $user->id = 'null';

                Auth::claims(['typ' => TokenType::ACCESS])->login($user);
            }
        }

        if (Auth::getClaim('typ') !== TokenType::ACCESS) {
            throw new AuthenticationException();
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
