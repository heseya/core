<?php

namespace App\Jobs;

use App\Services\Contracts\ShippingTimeDateServiceContract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class StopShippingUnlimitedStockDateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $shippingTimeDate = app(ShippingTimeDateServiceContract::class);
        $shippingTimeDate->stopShippingUnlimitedStockDate();
    }
}
