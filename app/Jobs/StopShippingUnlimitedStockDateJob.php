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
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        $shippingTimeDate = app(ShippingTimeDateServiceContract::class);
        $shippingTimeDate->stopShippingUnlimitedStockDate();
    }
}
