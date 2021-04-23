<?php

namespace App\Services\Contracts;

use Carbon\Carbon;

interface AnalyticsServiceContract
{
    public function getPaymentsOverPeriod(Carbon $from, Carbon $to, string $group): array;
}
