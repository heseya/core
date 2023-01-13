<?php

namespace App\Http\Middleware;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\AuthException;
use App\Exceptions\ClientException;
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

        if ($order->buyer_id === Auth::id() || Auth::user()?->hasPermissionTo('orders.show_details')) {
            return $next($request);
        }

        throw new ClientException(Exceptions::CLIENT_NO_ACCESS_TO_DOWNLOAD_DOCUMENT);
    }
}
