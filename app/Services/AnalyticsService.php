<?php

namespace App\Services;

use App\Models\Payment;
use App\Services\Contracts\AnalyticsServiceContract;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsService implements AnalyticsServiceContract
{
    public function getPaymentsOverPeriod(Carbon $from, Carbon $to, string $group): array
    {
        $amount = DB::raw('SUM(amount) AS amount');
        $count = DB::raw('COUNT(*) AS count');
        $key = $this->getGroupQuery($group);

        return Payment::select($amount, $count, $key)
            ->where('payed', true)
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->groupBy('key')
            ->get()
            ->mapWithKeys(fn ($item) => [
                $item['key'] => [
                    'amount' => $item['amount'],
                    'count' => $item['count'],
                ],
            ])->toArray();
    }

    private function getGroupQuery(string $group)
    {
        switch ($group) {
            case 'yearly':
                return DB::raw('YEAR(created_at) AS `key`');
            case 'monthly':
                return DB::raw('DATE_FORMAT(created_at, "%Y-%m") AS `key`');
            case 'daily':
                return DB::raw('DATE_FORMAT(created_at, "%Y-%m-%d") AS `key`');
            case 'hourly':
                return DB::raw('DATE_FORMAT(created_at, "%Y-%m-%d %H") AS `key`');
            default:
                return DB::raw('"total" AS `key`');
        }
    }
}
