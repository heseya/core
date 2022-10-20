<?php

namespace App\Observers;

use App\Models\ItemProduct;
use App\Services\Contracts\AvailabilityServiceContract;

class ItemProductObserver
{
    private AvailabilityServiceContract $availabilityService;

    public function __construct(AvailabilityServiceContract $availabilityService)
    {
        $this->availabilityService = $availabilityService;
    }

    public function created(ItemProduct $itemProduct): void
    {
        $this->availabilityService->calculateProductAvailability($itemProduct->product);
    }

    public function updated(ItemProduct $itemProduct): void
    {
        $this->availabilityService->calculateProductAvailability($itemProduct->product);
    }

    public function deleted(ItemProduct $itemProduct): void
    {
        $this->availabilityService->calculateProductAvailability($itemProduct->product);
    }
}
