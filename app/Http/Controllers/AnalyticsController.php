<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\AnalyticsControllerSwagger;
use App\Http\Requests\PaymentsAnalyticsRequest;
use App\Http\Resources\PaymentsAnalyticsResource;
use App\Services\Contracts\AnalyticsServiceContract;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalyticsController extends Controller implements AnalyticsControllerSwagger
{
    private AnalyticsServiceContract $analyticsService;

    public function __construct(AnalyticsServiceContract $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function paymentsTotal(PaymentsAnalyticsRequest $request): JsonResource
    {
        $from = $request->filled('from') ? Carbon::parse($request->input('from')) : Carbon::today();
        $to = $request->filled('to') ? Carbon::parse($request->input('to')) : Carbon::today()->subYear();

        $total = $this->analyticsService->getPaymentsOverPeriodTotal($from, $to);

        return PaymentsAnalyticsResource::make($total);
    }
}
