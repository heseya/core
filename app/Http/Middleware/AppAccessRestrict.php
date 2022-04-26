<?php

namespace App\Http\Middleware;

use App\Exceptions\AppAccessException;
use App\Models\App;
use Closure;
use Illuminate\Http\Request;

class AppAccessRestrict
{
    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->user() instanceof App) {
            throw new AppAccessException();
        }

        return $next($request);
    }
}
