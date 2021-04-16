<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Contracts\AnalyticsServiceContract;
use Carbon\Carbon;

class AnalyticsService implements AnalyticsServiceContract
{
    public function getPaymentsOverPeriodTotal(Carbon $from, Carbon $to): array
    {
        $query = Payment::where('payed', true)->whereDate(
            'created_at', '>=', $from,
        )->whereDate('created_at', '<=', $to);

        return [
            'amount' => $query->sum('amount'),
            'count' => $query->count(),
        ];
    }
}
