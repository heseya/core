<?php

namespace App\Http\Middleware;

use App\Exceptions\AppAccessException;
use App\Models\App;
use Closure;

class AppAccessRestrict
{
    public function handle($request, Closure $next): mixed
    {
        if ($request->user() instanceof App) {
            throw new AppAccessException();
        }

        return $next($request);
    }
}
