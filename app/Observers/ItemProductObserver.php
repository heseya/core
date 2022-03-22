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

    public function created(ItemProduct $itemProduct)
    {
        $this->availabilityService->calculateProductAvailability($itemProduct->product);
    }

    public function updated(ItemProduct $itemProduct)
    {
        $this->availabilityService->calculateProductAvailability($itemProduct->product);
    }

    public function deleted(ItemProduct $itemProduct)
    {
        $this->availabilityService->calculateProductAvailability($itemProduct->product);
    }

}
