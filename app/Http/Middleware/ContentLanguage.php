<?php

namespace App\Http\Middleware;

use App\Traits\GetPreferredLanguage;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;

class ContentLanguage
{
    use GetPreferredLanguage;

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        /** @var Response $response */
        $response = $next($request);

        return $response->header('Content-Language', Config::get('language.iso'));
    }
}
