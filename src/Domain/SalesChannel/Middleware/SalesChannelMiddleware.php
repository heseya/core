<?php

declare(strict_types=1);

namespace Domain\SalesChannel\Middleware;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ServerException;
use Closure;
use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Http\Request;

final readonly class SalesChannelMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        $request->request->add(['sales_channel' => SalesChannel::query()
            ->where('id', '=', $request->header('X-Sales-Channel', ''))
            ->firstOr(fn () => throw new ServerException(Exceptions::SERVER_NO_DEFAULT_SALES_CHANNEL)),
        ]);

        return $next($request);
    }
}
