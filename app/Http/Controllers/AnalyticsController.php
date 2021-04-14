<?php

namespace App\Http\Controllers;

use App\Http\Requests\RevenueAnalyticsRequest;
use App\Http\Resources\RevenueAnalyticsResource;
use App\Model\Order
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalyticsController extends Controller
{
    public function revenue(RevenueAnalyticsRequest $request): JsonResource
    {
        $orders = Order::all();

        if ($request->has('days')) {
            $orders = $orders->whereDate(
                'created_at',
                '>=',
                Carbon::today()->subDays($request->input('days')),
            );
        }

        $total = $orders->sum('payedAmount');

        return RevenueAnalyticsResource::make(['total' => $total]);
    }
}
