<?php

namespace App\Services;

use App\Models\Order;
use App\Services\Contracts\AnalyticsServiceContract;
use Carbon\Carbon;

class AnalyticsService implements AnalyticsServiceContract
{
    public function getTotalOrderRevenue(): float
    {
        return Order::all()->sum('payedAmount');
    }

    public function getPastDaysOrderRevenue(int $days): float
    {
        return Order::whereDate(
            'created_at',
            '>=',
            Carbon::today()->subDays($days),
        )->sum('payedAmount');
    }
}
