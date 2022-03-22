<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\TransformsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class UndotParams extends TransformsRequest
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     *
     * @return Response|RedirectResponse
     */
    public function handle($request, Closure $next)
    {
        if ($request->getQueryString() === null) {
            return $next($request);
        }

        if (Str::of($request->getQueryString())->match('/((\s*)+[\.]+(\S*)+[\=])/')->isEmpty()) {
            return $next($request);
        }

        $dotQueries = Str::of($request->getQueryString())
            ->explode('&')
            ->mapWithKeys(function ($param) {
                $exploded = explode('=', $param);
                return [Str::replace(['%5B', '%5D'], ['.', ''], $exploded[0]) => $exploded[1]];
            });

        $request->request->add(Arr::undot($dotQueries));
        $request->replace($request->request->all());

        return $next($request);
    }
}
