<?php

namespace App\Http\Middleware;

use App\Exceptions\AppAccessException;
use App\Models\App;
use App\Models\User;
use Closure;
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
