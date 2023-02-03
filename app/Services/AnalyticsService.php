<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\Contracts\AnalyticsServiceContract;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsService implements AnalyticsServiceContract
{
    public function getPaymentsOverPeriod(Carbon $from, Carbon $to, string $group): array
    {
        $amount = DB::raw('SUM(amount) AS amount');
        $count = DB::raw('COUNT(*) AS count');
        $key = $this->getGroupQuery($group);

        return Payment::query()
            ->select([$amount, $count, $key])
            ->where('status', PaymentStatus::SUCCESSFUL)
            // whereDate builds the same query as where, but compares only dates without time
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->groupBy('key')
            ->get()
            ->mapWithKeys(fn ($item) => [
                $item['key'] => [
                    'amount' => $item['amount'],
                    'count' => $item['count'],
                ],
            ])->toArray();
    }

    private function getGroupQuery(string $group): Expression
    {
        return match ($group) {
            'yearly' => DB::raw('YEAR(created_at) AS `key`'),
            'monthly' => DB::raw('DATE_FORMAT(created_at, "%Y-%m") AS `key`'),
            'daily' => DB::raw('DATE_FORMAT(created_at, "%Y-%m-%d") AS `key`'),
            'hourly' => DB::raw('DATE_FORMAT(created_at, "%Y-%m-%d %H") AS `key`'),
            default => DB::raw('"total" AS `key`'),
        };
    }
}
