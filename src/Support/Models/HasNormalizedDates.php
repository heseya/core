<?php

declare(strict_types=1);

namespace Support\Models;

use DateTimeInterface;
use Illuminate\Support\Carbon;

trait HasNormalizedDates
{
    // format for database
    public function getDateFormat(): string
    {
        return 'Y-m-d H:i:s';
    }

    protected function serializeDate(DateTimeInterface $date): string
    {
        // 2019-02-01T03:45:27+00:00
        return Carbon::instance($date)->toAtomString();
    }
}
