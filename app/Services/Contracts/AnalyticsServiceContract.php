<?php

namespace App\Services\Contracts;

use Carbon\Carbon;

interface AnalyticsServiceContract
{
    public function getPaymentsOverPeriodTotal(Carbon $from, Carbon $to): array;
}
