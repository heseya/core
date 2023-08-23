<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\Contracts\AnalyticsServiceContract;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsService implements AnalyticsServiceContract
{
    public function getPaymentsOverPeriod(Carbon $from, Carbon $to, string $group): array
    {
        $amount = DB::raw('SUM(amount) AS amount');
        $count = DB::raw('COUNT(*) AS count');
        $key = $this->getGroupQuery($group);

        /** @var Collection $collection */
        $collection = Payment::query()
            ->select([$amount, 'currency', $count, $key])
            ->where('status', PaymentStatus::SUCCESSFUL->value)
            // whereDate builds the same query as where, but compares only dates without time
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->groupBy('key', 'currency')
            ->get();

        $reduced = $collection->reduceWithKeys(function (array $carry, $item, $key): array {
            if (!array_key_exists($item['key'], $carry)) {
                $carry[$item['key']] = Arr::mapWithKeys(Currency::values(), fn (string $currency) => [
                    $currency => [
                        'amount' => 0,
                        'currency' => $currency,
                        'count' => 0,
                    ],
                ]);
            }

            $currency = $item['currency'] instanceof Currency
                ? $item['currency']->value
                : $item['currency'];

            $carry[$item['key']][$currency] = [
                'amount' => $item['amount'] instanceof Money
                    ? $item['amount']->getAmount()
                    : $item['amount'] . '',
                'currency' => $currency,
                'count' => $item['count'],
            ];

            return $carry;
        }, []);

        return collect($reduced)->map(fn ($item) => array_values($item))->toArray();
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
