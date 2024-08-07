<?php

namespace App\Http\Middleware;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use App\Models\App;
use App\Models\Payment;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IsAppPayment
{
    public function handle(Request $request, Closure $next): mixed
    {
        /** @var Payment $payment */
        $payment = $request->route('payment');

        // @phpstan-ignore-next-line
        if (Auth::user() instanceof App && $payment->paymentMethod?->app_id !== Auth::id()) {
            throw new ClientException(Exceptions::CLIENT_NO_ACCESS);
        }

        return $next($request);
    }
}
