<?php

namespace App\Http\Middleware;

use App\Enums\RoleType;
use App\Models\Role;
use App\Models\User;
use App\Models\App;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Tymon\JWTAuth\Http\Parser\Parser;
use Tymon\JWTAuth\JWT;
use Tymon\JWTAuth\Manager;
use Tymon\JWTAuth\Payload;

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

                Auth::claims(['typ' => 'access'])->login($user);
            }
        }

        if (Auth::payload()->get('typ') !== 'access') {
            throw new AuthenticationException();
        }

////        Token service
//
//        $token = Auth::getToken();
//
//        $jwt = new JWT(app(Manager::class), new Parser(new Request()));
//        $jwt->setToken($token);
//
//        dd($jwt->payload());

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
