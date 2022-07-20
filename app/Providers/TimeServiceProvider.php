<?php

namespace App\Providers;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;

class TimeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // DateTimeInterface::ATOM is the same as toIso8601String()
        Carbon::setToStringFormat(DateTimeInterface::ATOM);
        Carbon::serializeUsing(function ($date) {
            // 2019-02-01T03:45:27+00:00
            return Carbon::instance($date)->toIso8601String();
        });
    }
}
