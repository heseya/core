<?php

namespace App\Http\Middleware;

use App\Traits\GetPreferredLanguage;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContentLanguage
{
    use GetPreferredLanguage;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        /** @var Response $response */
        $response = $next($request);

        if ($response instanceof StreamedResponse) { // @phpstan-ignore-line
            return $response;
        }

        return $response->header('Content-Language', Config::get('language.iso'));
    }
}
