<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class Language
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     */
    public function handle($request, Closure $next): mixed
    {
        if ($request->hasHeader('x-language') && is_string($request->header('x-language'))) {
            App::setLocale($request->header('x-language'));
        }

        return $next($request);
    }
}
