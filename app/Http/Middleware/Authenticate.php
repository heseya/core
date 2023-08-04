<?php

namespace App\Http\Middleware;

use App\Enums\TokenType;
use App\Models\App;
use App\Services\Contracts\AuthServiceContract;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Support\Facades\App as AppFacade;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class Authenticate extends Middleware
{
    /**
     * @throws AuthenticationException
     */
    public function handle($request, Closure $next, ...$guards): mixed
    {
        if (!Auth::check()) {
            if (
                $request->hasHeader('Authorization')
                && Str::startsWith($request->header('Authorization'), ['Bearer', 'bearer'])
            ) {
                Config::set('auth.providers.users.model', App::class);
                Auth::forgetGuards();

                if (!Auth::check()) {
                    throw new AuthenticationException();
                }
            } else {
                /** @var AuthServiceContract $authService */
                $authService = AppFacade::make(AuthServiceContract::class);

                Auth::claims(['typ' => TokenType::ACCESS->value])
                    ->login($authService->unauthenticatedUser());
            }
        }

        if (Auth::getClaim('typ') !== TokenType::ACCESS->value) {
            throw new AuthenticationException();
        }

        return $next($request);
    }
}
