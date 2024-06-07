<?php

namespace App\Console;

use App\Jobs\CheckActiveSales;
use App\Jobs\GoogleCategoryJob;
use App\Jobs\StopShippingUnlimitedStockDateJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        $this->load(__DIR__ . '/../Utils/Commands');
    }

    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(new CheckActiveSales())
            ->everyThirtyMinutes()
            ->sentryMonitor('check-active-sales');

        // every hour at minute 43.
        $schedule->job(new StopShippingUnlimitedStockDateJob())
            ->cron('43 * * * *')
            ->sentryMonitor('stop-shipping-unlimited-stock-date');

        $schedule->job(new GoogleCategoryJob())
            ->weekly()
            ->sentryMonitor('google-category');
    }
}
