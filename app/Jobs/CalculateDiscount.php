<?php

namespace App\Jobs;

use App\Models\Discount;
use App\Services\DiscountService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CalculateDiscount implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected Discount $discount;
    protected bool $updated;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Discount $discount, bool $updated = false)
    {
        $this->discount = $discount;
        $this->updated = $updated;
    }

    /**
     * Execute the job.
     */
    public function handle(DiscountService $discountService): void
    {
        $discountService->calculateDiscount($this->discount);
    }
}
