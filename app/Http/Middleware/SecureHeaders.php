<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SecureHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        if ($response instanceof Response) {
            $response->withHeaders([
                'Server' => 'Heseya',
                'X-Powered-By' => 'Heseya',
                'X-Frame-Options' => 'DENY',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        return $response;
    }
}
