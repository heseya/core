<?php

namespace App\Http\Middleware;

use App\Models\App;
use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Support\Facades\Hash;

class Authenticate extends Middleware
{
    public function handle($request, Closure $next, ...$guards)
    {
        if (
            $request->hasHeader('x-app-id') &&
            $request->hasHeader('x-app-key') &&
            $this->authenticateApp($request->header('x-app-id'), $request->header('x-app-key'))
        ) {
            return $next($request);
        }

        return parent::handle($request, $next, ...$guards);
    }

    protected function authenticateApp(string $id, string $key): bool
    {
        $app = App::findOrFail($id);

        return $app && Hash::check($key, $app->key);
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo($request)
    {
        return null;
    }
}
