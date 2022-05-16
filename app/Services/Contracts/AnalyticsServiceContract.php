<?php

namespace App\Services\Contracts;

use Illuminate\Support\Carbon;

interface AnalyticsServiceContract
{
    public function getPaymentsOverPeriod(Carbon $from, Carbon $to, string $group): array;
}
