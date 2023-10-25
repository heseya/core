<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CacheResponse
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        /** @var Response|StreamedResponse $response */
        $response = $next($request);

        if ($response instanceof StreamedResponse) {
            return $response;
        }

        $key = $request->header('Authorization') ? 'auth' : 'public';

        if ($request->page && !$request->is('pages/id:*')) {
            $uri = '/pages/';
        } elseif ($request->product_set && !$request->is('product-sets/id:*')) {
            $uri = '/product-sets/';
        } else {
            $uri = $request->getRequestUri();
        }

        $value = Config::get('response-cache.' . $key . '.' . $request->method() . '.' . $uri);

        return $response->header('Cache-Control', $value ? "max-age={$value}" : 'no-cache, private');
    }
}
