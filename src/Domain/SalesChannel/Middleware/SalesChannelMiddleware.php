<?php

declare(strict_types=1);

namespace Domain\SalesChannel\Middleware;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use Closure;
use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

final readonly class SalesChannelMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        /** @var SalesChannel $sales_channel */
        $sales_channel = SalesChannel::query()
            ->where('id', '=', $request->header('X-Sales-Channel', ''))
            ->firstOr(fn () => throw new ClientException(Exceptions::CLIENT_SALES_CHANNEL_NOT_FOUND));

        $vat_rate = ((float) $sales_channel->vat_rate) * 0.01;

        Cache::put('vat_rate', $vat_rate);

        return $next($request);
    }
}
