<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\AnalyticsControllerSwagger;
use App\Http\Requests\AnalyticsPaymentsRequest;
use App\Http\Resources\AnalyticsPaymentsResource;
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

    public function payments(AnalyticsPaymentsRequest $request): JsonResource
    {
        $from = $request->filled('from') ? Carbon::parse($request->input('from')) : Carbon::today()->subYear();
        $to = $request->filled('to') ? Carbon::parse($request->input('to')) : Carbon::now();
        $group = $request->input('group', 'total');

        $payments = $this->analyticsService->getPaymentsOverPeriod($from, $to, $group);

        return AnalyticsPaymentsResource::make($payments);
    }
}
