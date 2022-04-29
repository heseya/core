<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\TransformsRequest;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\HeaderUtils;

class UndotParams extends TransformsRequest
{
    /**
     * Handle an incoming request.
     */
    public function handle($request, Closure $next): mixed
    {
        $query = HeaderUtils::parseQuery($request->server->get('QUERY_STRING'));

        $request->request->add(Arr::undot($query));
        $request->replace($request->request->all());

        return $next($request);
    }
}
