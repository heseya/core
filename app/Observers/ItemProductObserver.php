<?php

namespace App\Observers;

use App\Models\ItemProduct;
use App\Services\Contracts\AvailabilityServiceContract;

class ItemProductObserver
{
    public function __construct(
        private AvailabilityServiceContract $availabilityService,
    ) {
    }

    public function created(ItemProduct $itemProduct): void
    {
        if ($itemProduct->product !== null) {
            $this->availabilityService->calculateProductAvailability($itemProduct->product);
        }
    }

    public function updated(ItemProduct $itemProduct): void
    {
        if ($itemProduct->product !== null) {
            $this->availabilityService->calculateProductAvailability($itemProduct->product);
        }
    }

    public function deleted(ItemProduct $itemProduct): void
    {
        if ($itemProduct->product !== null) {
            $this->availabilityService->calculateProductAvailability($itemProduct->product);
        }
    }
}
