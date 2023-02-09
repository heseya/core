<?php

namespace App\Http\Middleware;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use App\Models\Payment;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class ForceSQL
{
    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->boolean('force_database_search')) {
            Config::set('scout.driver', 'database');
        }

        return $next($request);
    }
}
