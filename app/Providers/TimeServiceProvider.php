<?php

namespace App\Providers;

use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;

class TimeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Carbon::setToStringFormat('c');
    }
}
