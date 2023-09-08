<?php

declare(strict_types=1);

namespace Domain\SalesChannel\Middleware;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use Closure;
use Domain\SalesChannel\SalesChannelRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Throwable;

final class WithSalesChannel
{
    public function handle(Request $request, Closure $next): mixed
    {
        $header = $request->header('Sales-Channel');

        try {
            $sales_channel = empty($header)
                ? app(SalesChannelRepository::class)->getDefault()
                : app(SalesChannelRepository::class)->getOne($header);
        } catch (Throwable $th) {
            throw new ClientException(Exceptions::CLIENT_SALES_CHANNEL_NOT_FOUND, $th);
        }

        Config::set('sales-channel.model', $sales_channel);

        return $next($request);
    }
}
