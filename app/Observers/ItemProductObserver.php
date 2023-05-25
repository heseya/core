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
        $this->calculateProductAvailability($itemProduct);
    }

    public function updated(ItemProduct $itemProduct): void
    {
        $this->calculateProductAvailability($itemProduct);
    }

    public function deleted(ItemProduct $itemProduct): void
    {
        $this->calculateProductAvailability($itemProduct);
    }

    private function calculateProductAvailability(ItemProduct $itemProduct): void
    {
        if ($itemProduct->product !== null) {
            $this->availabilityService->calculateProductAvailability($itemProduct->product);
        }
    }
}
