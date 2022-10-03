<?php

namespace App\Jobs;

use App\Services\DiscountService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckActiveSales implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     *
     * @param DiscountService $discountService
     */
    public function handle(DiscountService $discountService): void
    {
        $discountService->checkActiveSales();
    }
}
