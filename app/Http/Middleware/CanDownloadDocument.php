<?php

namespace App\Http\Middleware;

use App\Exceptions\AuthException;
use App\Models\Order;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CanDownloadDocument
{
    /**
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     *
     * @throws AuthException
     */
    public function handle(Request $request, Closure $next)
    {
        /** @var Order $order */
        $order = $request->route('order');

        if ($order->user_id === Auth::id() || Auth::user()->hasPermissionTo('orders.show_details')) {
            return $next($request);
        }

        throw new AuthException('No access.');
    }
}
