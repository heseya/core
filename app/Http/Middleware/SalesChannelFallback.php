<?php

namespace App\Http\Middleware;

use Closure;
use Domain\SalesChannel\SalesChannelCrudService;
use Illuminate\Http\Request;

class SalesChannelFallback
{
    public function handle(Request $request, Closure $next): mixed
    {
        $saleChannel = $request->header('X-Sales-Channel');
        if ($saleChannel) {
            /** @var SalesChannelCrudService $saleChannelService */
            $saleChannelService = app(SalesChannelCrudService::class);

            if (!$saleChannelService->userHasAccess($saleChannel)) {
                $channel = $saleChannelService->getDefault();
                $headers = $request->header();
                $headers['X-Sales-Channel'] = $channel->getKey();

                $request->headers->replace($headers);
            }
        }

        return $next($request);
    }
}
