<?php

namespace App\Http\Middleware;

use App\Exceptions\AppAccessException;
use App\Models\User;
use Closure;
use Domain\App\Models\App;
use Illuminate\Http\Request;

class AppAccessRestrict
{
    public function handle(Request $request, Closure $next): mixed
    {
        /** @var App|User|null $user */
        $user = $request->user();

        if ($user instanceof App) {
            throw new AppAccessException();
        }

        return $next($request);
    }
}
