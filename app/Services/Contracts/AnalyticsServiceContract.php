<?php

namespace App\Services\Contracts;

use App\Models\Order;

interface AnalyticsServiceContract
{
    public function getTotalOrderRevenue(): float;

    public function getPastDaysOrderRevenue(int $days): float;
}
